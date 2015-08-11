<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\Restart as RestartException;
use Sprint\Migration\Exceptions\Migration as MigrationException;

class VersionManager
{

    private $restarts = array();

    protected $checkPermissions = 1;

    protected $db = null;
    
    public function __construct() {
         $this->db = new Db();
    }

    public function startMigration($versionName, $action = 'up', $params = array()) {
        try {

            $action = ($action == 'up') ? 'up' : 'down';

            if (isset($this->restarts[$versionName])){
                unset($this->restarts[$versionName]);
            }

            $versionInstance = $this->getVersionInstance($versionName);
            
            if (!$versionInstance) {
                throw new MigrationException('failed to initialize migration');
            }
            
            if ($this->checkPermissions) {
                
                $versionType = $this->getVersionType($versionName);
                
                if (!$versionType || $versionType == 'is_unknown') {
                    throw new MigrationException('migration not found');
                }

                if ($action == 'up' && $versionType != 'is_new') {
                    throw new MigrationException('migration already up');
                }

                if ($action == 'down' && $versionType != 'is_success') {
                    throw new MigrationException('migration already down');
                }
            }

            $versionInstance->setParams($params);

            if ($action == 'up'){
                $ok = $versionInstance->up();
            } else {
                $ok = $versionInstance->down();
            }

            if (Env::getApp()->GetException()){
                throw new MigrationException(Env::getApp()->GetException()->GetString());
            }

            if ($ok === false) {
                throw new MigrationException('migration returns false');
            }

            if ($action == 'up'){

                $descr = $this->getVersionDescription($versionInstance);
                $filecode = $this->getVersionFileCode($versionName);

                $ok = $this->db->addRecord($versionName, $descr, $filecode);
            } else {
                $ok = $this->db->removeRecord($versionName);
            }

            if ($ok === false) {
                throw new MigrationException('unable to write migration to the database');
            }

            Out::outToConsoleOnly('%s (%s) success', $versionName, $action);
            return true;

        } catch (RestartException $e){
            $this->restarts[$versionName] = isset($versionInstance) ? $versionInstance->getParams() : array();

        } catch (MigrationException $e) {
            Out::outError('%s (%s) error: %s', $versionName, $action, $e->getMessage());

        } catch (\Exception $e) {
            Out::outError('%s (%s) error: %s', $versionName, $action, $e->getMessage());
        }

        return false;
    }


    public function needRestart($version){
        return (isset($this->restarts[$version])) ? 1 : 0;
    }

    public function getRestartParams($version){
        return $this->restarts[$version];
    }

    public function getMigrationDescription($versionName) {
        $descr = array('description' => '', 'location' => '');
        $instance = $this->getVersionInstance($versionName);
        if ($instance){
            $descr['description'] = $this->getVersionDescription($instance);
            $descr['location'] = $this->getVersionFile($versionName);
        } else {
            $record = $this->db->getRecordByName($versionName)->Fetch();
            $descr['description'] = ($record && isset($record['description'])) ? $record['description'] : '';
        }

        return $descr;
    }

    public function getVersionDescription($version){
        $instance = ($version instanceof Version);
        if (!$instance){
            $instance = $this->getVersionInstance($version);
        }

        return $this->prepareDescription($instance->getDescription());
    }

    public function createVersionFile($description = '') {
        $description = $this->prepareDescription($description);

        $originTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Moscow');
        $versionName = 'Version' . date('YmdHis');
        date_default_timezone_set($originTz);

        $str = $this->renderVersionFile(array(
            'version' => $versionName,
            'description' => $description,
        ));
        $file = $this->getVersionFile($versionName);
        file_put_contents($file, $str);

        if (!is_file($file)){
            Out::outError('%s, error: can\'t create a file "%s"', $versionName, $file);
            return false;
        }

        return array(
            'version' => $versionName,
            'location' => $file,
            'description' => $description,
        );
    }


    public function getVersions($for = 'all') {
        $for = in_array($for, array('all', 'up', 'down', 'unknown')) ? $for : 'all';

        $records = array();
        $files = array();

        /* @var $dbres \CDBResult */
        $dbres = $this->db->getRecords();
        while ($aItem = $dbres->Fetch()) {
            if ($this->checkVersionName($aItem['version'])) {
                $records[] = $aItem['version'];
            }
        }

        /* @var $item \SplFileInfo */
        $directory = new \DirectoryIterator(Env::getMigrationDir());
        foreach ($directory as $item) {
            $fileName = pathinfo($item->getPathname(), PATHINFO_FILENAME);
            if ($this->checkVersionName($fileName)) {
                $files[] = $fileName;
            }
        }

        $merge = array_merge($records, $files);
        $merge = array_unique($merge);

        if ($for == 'down' || $for == 'unknown') {
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
                ($for == 'unknown' && $type == 'is_unknown') ||
                ($for == 'all')){

                $result[] = array(
                    'type' => $type,
                    'version' => $val,
                );
            }

        }

        return $result;
    }

    public function getStatus(){
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

    public function checkPermissions($check = 1){
        $this->checkPermissions = $check;
    }

    public function restoreUnknown($versionName){
        $ok = false;

        if ('is_unknown' == $this->getVersionType($versionName)){
            $record = $this->db->getRecordByName($versionName)->Fetch();
            if (!empty($record['filecode'])){
                $file = $this->getVersionFile($versionName);
                file_put_contents($file, $record['filecode']);
                $ok = is_file($file) ? $versionName : false;
            }
        }
        return $ok;
    }

    public function removeUnknown($versionName){
        $ok = false;
        if ($this->checkVersionName($versionName)){
            $file = $this->getVersionFile($versionName);
            if (is_file($file)){
                $ok = unlink($file);
            }
        }
        return $ok;
    }

    /**
     * @param $versionName
     * @return Version
     */
    protected function getVersionInstance($versionName) {
        if (!$this->checkVersionName($versionName)) {
            return false;
        }

        $file = $this->getVersionFile($versionName);
        if (!file_exists($file)){
            return false;
        }

        ob_start();
        require_once($file);
        ob_end_clean();

        $class = 'Sprint\Migration\\' . $versionName;
        if (!class_exists($class)) {
            return false;
        }

        $obj = new $class;
        return $obj;
    }

    protected function getVersionType($versionName) {
        if (!$this->checkVersionName($versionName)){
            return false;
        }

        $record = $this->db->getRecordByName($versionName)->Fetch();
        $file = $this->getVersionFile($versionName);

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

        return $type;
    }

    public function getVersionFileCode($versionName){
        $verfile = $this->getVersionFile($versionName);
        return is_file($verfile) ? file_get_contents($verfile) : '';
    }

    protected function getVersionFile($versionName) {
        return Env::getMigrationDir() . '/'.$versionName . '.php';
    }

    protected function checkVersionName($versionName) {
        return preg_match('/^Version\d+$/i', $versionName);
    }

    protected function renderVersionFile($vars = array()) {
        if (is_array($vars)) {
            extract($vars, EXTR_SKIP);
        }

        ob_start();

        include(Env::getVersionTemplateFile());

        $html = ob_get_clean();

        return $html;
    }


    protected function prepareDescription($descr = ''){
        $descr = strval($descr);
        $descr = nl2br( $descr);
        $descr = strip_tags($descr);
        $descr = addslashes($descr);
        return $descr;
    }

}
