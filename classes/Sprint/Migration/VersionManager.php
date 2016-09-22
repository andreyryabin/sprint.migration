<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exceptions\MigrationException;

class VersionManager
{

    private $restarts = array();

    protected $checkPerms = 1;

    protected $versionTable = null;

    public function __construct() {
        $this->versionTable = new VersionTable();
    }

    public function startMigration($versionName, $method = 'up', $params = array(), $restart = 0) {
        /* @global $APPLICATION \CMain */
        global $APPLICATION;

        if (isset($this->restarts[$versionName])){
            unset($this->restarts[$versionName]);
        }

        try {

            $method = ($method == 'up') ? 'up' : 'down';

            $meta = $this->getVersionMeta($versionName);

            if (!$meta || empty($meta['class'])) {
                throw new MigrationException('failed to initialize migration');
            }

            if ($this->checkPerms) {
                if ($meta['status'] == 'is_unknown') {
                    throw new MigrationException('migration not found');
                }

                if ($method == 'up' && $meta['status'] != 'is_new') {
                    throw new MigrationException('migration already up');
                }

                if ($method == 'down' && $meta['status'] != 'is_installed') {
                    throw new MigrationException('migration already down');
                }
            }

            if (!$restart){
                Out::outToConsoleOnly('%s (%s) start', $versionName, $method);
                if ($method == 'up'){
                    Out::outToHtmlOnly('[green]%s (%s) start[/]', $versionName, $method);
                } else {
                    Out::outToHtmlOnly('[red]%s (%s) start[/]', $versionName, $method);
                }
            }

            /** @var $versionInstance Version */
            $versionInstance = new $meta['class'];
            $versionInstance->setParams($params);

            if ($method == 'up') {
                $ok = $versionInstance->up();
            } else {
                $ok = $versionInstance->down();
            }

            if ($APPLICATION->GetException()) {
                throw new MigrationException($APPLICATION->GetException()->GetString());
            }

            if ($ok === false) {
                throw new MigrationException('migration returns false');
            }

            if ($method == 'up') {
                $ok = $this->versionTable->addRecord($versionName);
            } else {
                $ok = $this->versionTable->removeRecord($versionName);
            }

            if ($ok === false) {
                throw new MigrationException('unable to write migration to the database');
            }

            Out::out('%s (%s) success', $versionName, $method);
            return true;

        } catch (RestartException $e) {
            $this->restarts[$versionName] = isset($versionInstance) ? $versionInstance->getParams() : array();

        } catch (MigrationException $e) {
            Out::outError('%s (%s) error: %s', $versionName, $method, $e->getMessage());

        } catch (\Exception $e) {
            Out::outError('%s (%s) error: %s', $versionName, $method, $e->getMessage());
        }

        return false;
    }


    public function needRestart($version) {
        return (isset($this->restarts[$version])) ? 1 : 0;
    }

    public function getRestartParams($version) {
        return $this->restarts[$version];
    }

    public function createVersionFile($description = '', $prefix = '') {
        $description = $this->purifyDescriptionForFile($description);
        $prefix = $this->purifyPrefix($prefix);

        $originTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Moscow');
        $ts = date('YmdHis');
        date_default_timezone_set($originTz);

        $versionName = $prefix . $ts;

        list($extendUse, $extendClass) = explode(' as ', Module::getMigrationExtendClass());
        $extendUse = trim($extendUse);
        $extendClass = trim($extendClass);

        if (!empty($extendClass)) {
            $extendUse = 'use ' . $extendUse . ' as ' . $extendClass . ';' . PHP_EOL;
        } else {
            $extendClass = $extendUse;
            $extendUse = '';
        }

        $str = $this->renderFile(Module::getMigrationTemplate(), array(
            'version' => $versionName,
            'description' => $description,
            'extendUse' => $extendUse,
            'extendClass' => $extendClass,
        ));

        $file = $this->getVersionFile($versionName);
        file_put_contents($file, $str);

        if (!is_file($file)) {
            Out::outError('%s, error: can\'t create a file "%s"', $versionName, $file);
            return false;
        }

        return $this->getVersionMeta($versionName);
    }


    public function getVersions($filter = array()) {
        if (empty($filter['for'])){
            $filter['for'] = 'all';
        }

        if (!in_array($filter['for'], array('all', 'up', 'down', 'unknown'))){
            $filter['for'] = 'all';
        }

        $records = array();
        /* @var $dbres \CDBResult */
        $dbres = $this->versionTable->getRecords();
        while ($aItem = $dbres->Fetch()) {
            $ts = $this->getVersionTimestamp($aItem['version']);
            if ($ts) {
                $records[$aItem['version']] = $ts;
            }
        }

        $files = array();
        /* @var $item \SplFileInfo */
        $directory = new \DirectoryIterator(Module::getMigrationDir());
        foreach ($directory as $item) {
            $fileName = pathinfo($item->getPathname(), PATHINFO_FILENAME);
            $ts = $this->getVersionTimestamp($fileName);
            if ($ts) {
                $files[$fileName] = $ts;
            }
        }

        $merge = array_merge($records, $files);
        if ($filter['for'] == 'down' || $filter['for'] == 'unknown') {
            arsort($merge);
        } else {
            asort($merge);
        }

        $result = array();
        foreach ($merge as $version => $ts) {
            $isRecord = array_key_exists($version, $records);
            $isFile = array_key_exists($version, $files);

            $meta = $this->prepVersionMeta($version, $isFile, $isRecord);

            if (($filter['for'] == 'up' && $meta['status'] == 'is_new') ||
                ($filter['for'] == 'down' && $meta['status'] == 'is_installed') ||
                ($filter['for'] == 'unknown' && $meta['status'] == 'is_unknown') ||
                ($filter['for'] == 'all')
            ) {

                if (!empty($filter['search'])){

                    $textindex = Module::convertToUtf8IfNeed($meta['version'] . $meta['description']);
                    $searchword = Module::convertToUtf8IfNeed($filter['search']);

                    if (false !== mb_stripos($textindex, $searchword, null, 'utf-8')){
                        $result[] = $meta;
                    }

                } else {
                    $result[] = $meta;
                }


            }
        }
        return $result;
    }

    protected function prepVersionMeta($versionName, $isFile, $isRecord) {
        if (!$isRecord && !$isFile){
            return false;
        }

        $file = $this->getVersionFile($versionName);

        $meta = array(
            'is_file' => $isFile,
            'is_record' => $isRecord,
        );

        if ($isRecord && $isFile) {
            $meta['status'] = 'is_installed';
        } elseif (!$isRecord && $isFile) {
            $meta['status'] = 'is_new';
        } else {
            $meta['status'] = 'is_unknown';
        }

        $meta['version'] = $versionName;

        if (!$isFile) {
            return $meta;
        }

        $meta['location'] = $file;

        ob_start();
        /** @noinspection PhpIncludeInspection */
        require_once($file);
        ob_end_clean();

        $class = 'Sprint\Migration\\' . $versionName;
        if (!class_exists($class)) {
            return $meta;
        }

        $descr = '';
        if (!method_exists($class, '__construct')) {
            /** @var $versionInstance Version */
            $versionInstance = new $class;
            $descr = $versionInstance->getDescription();
        } elseif (class_exists('\ReflectionClass')) {
            $reflect = new \ReflectionClass($class);
            $props = $reflect->getDefaultProperties();
            $descr = $props['description'];
        }

        $meta['class'] = $class;
        $meta['description'] = $this->purifyDescriptionForMeta($descr);

        return $meta;

    }

    public function getVersionMeta($versionName) {
        if ($this->checkVersionName($versionName)) {
            $isRecord = $this->isRecordExists($versionName);
            $isFile = $this->isFileExists($versionName);
            return $this->prepVersionMeta($versionName, $isFile, $isRecord);
        }
        return false;
    }

    public function checkPermissions($check = 1) {
        $this->checkPerms = $check;
    }


    protected function getVersionFile($versionName) {
        return Module::getMigrationDir() . '/' . $versionName . '.php';
    }

    protected function isFileExists($versionName) {
        $file = $this->getVersionFile($versionName);
        return file_exists($file) ? 1 : 0;
    }

    protected function isRecordExists($versionName) {
        $record = $this->versionTable->getRecordByName($versionName)->Fetch();
        return (empty($record)) ? 0 : 1;
    }


    public function checkVersionName($versionName) {
        return $this->getVersionTimestamp($versionName) ? true : false;
    }

    public function getVersionTimestamp($versionName) {
        $matches = array();
        if (preg_match('/[0-9]{14}$/', $versionName, $matches)) {
            return $matches[0];
        } else {
            return false;
        }
    }

    protected function renderFile($file, $vars = array()) {
        if (is_array($vars)) {
            extract($vars, EXTR_SKIP);
        }

        ob_start();

        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            include $file;
        }

        $html = ob_get_clean();

        return $html;
    }


    protected function purifyPrefix($prefix = '') {
        $prefix = trim($prefix);
        $default = 'Version';

        if (empty($prefix)) {
            return $default;
        }

        $prefix = preg_replace("/[^a-z0-9_]/i", '', $prefix);
        if (empty($prefix)) {
            return $default;
        }

        if (preg_match('/^\d/', $prefix)) {
            return $default;
        }

        return $prefix;
    }

    protected function purifyDescriptionForFile($descr = '') {
        $descr = strval($descr);
        $descr = str_replace(array("\n\r", "\r\n", "\n","\r"), ' ', $descr );
        $descr = strip_tags($descr);
        $descr = addslashes($descr);
        return $descr;
    }

    protected function purifyDescriptionForMeta($descr = '') {
        $descr = strval($descr);
        $descr = str_replace(array("\n\r", "\r\n", "\n","\r"), ' ', $descr );
        $descr = strip_tags($descr);
        $descr = stripslashes($descr);
        return $descr;
    }

}
