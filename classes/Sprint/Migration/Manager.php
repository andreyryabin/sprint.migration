<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\Restart as RestartException;
use Sprint\Migration\Exceptions\Migration as MigrationException;

class Manager
{

    private $restarts = array();

    protected $force = 0;

    public function __construct() {
         Db::createTablesIfNotExists();
    }

    public function startMigration($version, $action = 'up', $params = array()) {
        try {

            $action = ($action == 'up') ? 'up' : 'down';

            if (isset($this->restarts[$version])){
                unset($this->restarts[$version]);
            }

            if (!$this->force) {
                $aItem = $this->getVersionByName($version);
                if (!$aItem || $aItem['type'] == 'is_unknown') {
                    throw new MigrationException('migration not found');
                }

                if ($action == 'up' && $aItem['type'] != 'is_new') {
                    throw new MigrationException('migration already up');
                }

                if ($action == 'down' && $aItem['type'] != 'is_success') {
                    throw new MigrationException('migration already down');
                }
            }

            $oVersion = $this->getVersionInstance($version);
            if (!$oVersion) {
                throw new MigrationException('failed to initialize migration');
            }

            $oVersion->setParams($params);

            if ($action == 'up'){
                $ok = $oVersion->up();
            } else {
                $ok = $oVersion->down();
            }

            if ($ok === false) {
                throw new \Exception('migration returns false');
            }

            if ($action == 'up'){
                $this->addRecord($version);
            } else {
                $this->removeRecord($version);
            }

            Out::outToConsoleOnly('%s (%s) success', $version, $action);

            return true;

        } catch (RestartException $e){
            $this->restarts[$version] = isset($oVersion) ? $oVersion->getParams() : array();

        } catch (MigrationException $e) {
            Out::outError('%s (%s) error: %s', $version, $action, $e->getMessage());

        } catch (\Exception $e) {
            Out::outError('%s (%s) error: %s', $version, $action, $e->getMessage());
        }

        return false;
    }


    public function needRestart($version){
        return (isset($this->restarts[$version])) ? 1 : 0;
    }

    public function getRestartParams($version){
        return $this->restarts[$version];
    }

    public function getDescription($version) {
        $oVersion = $this->getVersionInstance($version);
        return ($oVersion) ? $oVersion->getDescription() : '';
    }

    public function createMigrationFile($description = '') {
        $description = preg_replace("/\r\n|\r|\n/", '<br/>', $description);
        $description = strip_tags($description);
        $description = addslashes($description);

        $originTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Moscow');
        $version = 'Version' . date('YmdHis');
        date_default_timezone_set($originTz);

        $str = $this->renderVersionFile(array(
            'version' => $version,
            'description' => $description,
        ));
        $file = $this->getVersionFile($version);
        file_put_contents($file, $str);

        if (is_file($file)){
            Out::out('%s created', $version);
            return $version;
        } else {
            Out::out('%s, error: can\'t create a file "%s"', $version, $file);
            return false;
        }
    }


    public function getVersions($for = 'all') {
        $for = in_array($for, array('all', 'up', 'down')) ? $for : 'all';

        $records = $this->getRecords();
        $files = $this->getFiles();

        $merge = array_merge($records, $files);
        $merge = array_unique($merge);

        if ($for == 'down') {
            rsort($merge);
        } else {
            sort($merge);
        }

        $result = array();
        foreach ($merge as $val) {

            $isRecord = in_array($val, $records);
            $isFile = in_array($val, $files);

            if ($isRecord && $isFile) {
                $type = 'is_success';
            } elseif (!$isRecord && $isFile) {
                $type = 'is_new';
            } else {
                $type = 'is_unknown';
            }

            if (($for == 'up' && $type == 'is_new') ||
                ($for == 'down' && $type == 'is_success') ||
                ($for == 'all')){

                $result[] = array(
                    'type' => $type,
                    'version' => $val,
                );
            }

        }

        return $result;
    }

    protected function getVersionByName($name) {
        if (!$this->checkName($name)){
            return false;
        }

        $record = Db::findByName($name)->Fetch();
        $file = $this->getVersionFile($name);

        $isRecord = !empty($record);
        $isFile = file_exists($file);

        if (!$isRecord && !$isFile){
            return false;
        }

        if ($isRecord && $isFile) {
            $type = 'is_success';
        } elseif (!$isRecord && $isFile) {
            $type = 'is_new';
        } else {
            $type = 'is_unknown';
        }

        return array(
            'type' => $type,
            'version' => $name,
        );
    }


    public function getSummaryVersions(){
        $versions = $this->getVersions('all');

        $summ = array(
            'is_new' => 0,
            'is_success' => 0,
            'is_unknown' => 0,
        );

        foreach ($versions as $aItem) {
            $type = $aItem['type'];
            $summ[$type]++;
        }

        return $summ;
    }

    public function enableForce(){
        $this->force = 1;
    }

    protected function executeAll($action = 'up', $limit = 0) {
        $action = ($action == 'up') ? 'up' : 'down';
        $limit = (int)$limit;

        $success = 0;

        $versions = $this->getVersions($action);
        foreach ($versions as $aItem) {
            if ($this->executeOnce($aItem['version'], $action)) {
                $success++;
            }

            if ($limit > 0 && $limit == $success) {
                break;
            }
        }

        Out::out('migrations %s: %d', $action, $success);

        return $success;
    }

    protected function executeOnce($version, $action = 'up') {
        $action = ($action == 'up') ? 'up' : 'down';
        $params = array();

        do {
            $restart = 0;
            $ok = $this->startMigration($version, $action, $params);
            if ($this->needRestart($version)) {
                $params = $this->getRestartParams($version);
                $restart = 1;
            }

        } while ($restart == 1);

        return $ok;
    }

    protected function getFiles() {
        $directory = new \DirectoryIterator(Utils::getMigrationDir());
        $files = array();
        /* @var $item \SplFileInfo */
        foreach ($directory as $item) {
            $fileName = pathinfo($item->getPathname(), PATHINFO_FILENAME);
            if ($this->checkName($fileName)) {
                $files[] = $fileName;
            }
        }

        return $files;
    }

    protected function getRecords() {
        $dbResult = Db::findAll();

        $records = array();
        while ($aItem = $dbResult->Fetch()) {
            if ($this->checkName($aItem['version'])) {
                $records[] = $aItem['version'];
            }

        }
        return $records;
    }

    protected function addRecord($versionName) {
        if ($this->checkName($versionName)) {
            return Db::addRecord($versionName);
        }
        return false;
    }

    protected function removeRecord($versionName) {
        if ($this->checkName($versionName)) {
            return Db::removeRecord($versionName);
        }
        return false;
    }

    /* @return Version */
    protected function getVersionInstance($versionName) {
        $file = false;
        if ($this->checkName($versionName)) {
            $file = $this->getVersionFile($versionName);
        }

        if (!$file || !file_exists($file)) {
            return false;
        }

        include_once $file;

        $class = 'Sprint\Migration\\' . $versionName;
        if (!class_exists($class)) {
            return false;
        }

        $obj = new $class;
        return $obj;
    }

    protected function getVersionFile($versionName) {
        return Utils::getMigrationDir() . '/'.$versionName . '.php';
    }

    protected function checkName($versionName) {
        return preg_match('/^Version\d+$/i', $versionName);
    }

    protected function renderVersionFile($vars = array()) {
        if (is_array($vars)) {
            extract($vars, EXTR_SKIP);
        }

        ob_start();

        include(Utils::getVersionTemplateFile());

        $html = ob_get_clean();

        return $html;
    }


}
