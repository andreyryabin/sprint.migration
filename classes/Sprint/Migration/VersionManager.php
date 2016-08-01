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

    public function startMigration($versionName, $action = 'up', $params = array()) {
        /* @global $APPLICATION \CMain */
        global $APPLICATION;

        try {

            $action = ($action == 'up') ? 'up' : 'down';

            if (!$this->checkVersionName($versionName)) {
                throw new MigrationException('invalid version name');
            }

            $file = $this->getVersionFile($versionName);
            if (!file_exists($file)){
                throw new MigrationException('version file not found');
            }

            ob_start();
            /** @noinspection PhpIncludeInspection */
            require_once($file);
            ob_end_clean();

            $versionClass = 'Sprint\Migration\\' . $versionName;
            if (!class_exists($versionClass)) {
                throw new MigrationException('version class not found');
            }

            if ($this->checkPerms) {
                
                $versionType = $this->getVersionType($versionName);
                
                if (!$versionType || $versionType == 'is_unknown') {
                    throw new MigrationException('migration not found');
                }

                if ($action == 'up' && $versionType != 'is_new') {
                    throw new MigrationException('migration already up');
                }

                if ($action == 'down' && $versionType != 'is_installed') {
                    throw new MigrationException('migration already down');
                }
            }

            if (isset($this->restarts[$versionName])){
                unset($this->restarts[$versionName]);
            } else {
                Out::outToConsoleOnly('%s (%s) start', $versionName, $action);
            }

            /** @var $versionInstance Version */
            $versionInstance = new $versionClass;
            $versionInstance->setParams($params);

            if ($action == 'up'){
                $ok = $versionInstance->up();
            } else {
                $ok = $versionInstance->down();
            }

            if ($APPLICATION->GetException()){
                throw new MigrationException($APPLICATION->GetException()->GetString());
            }

            if ($ok === false) {
                throw new MigrationException('migration returns false');
            }

            if ($action == 'up'){
                $ok = $this->versionTable->addRecord($versionName);
            } else {
                $ok = $this->versionTable->removeRecord($versionName);
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

    public function getVersionDescription($versionName) {
        $result = array('description' => '', 'location' => '');

        if (!$this->checkVersionName($versionName)) {
            return $result;
        }

        $file = $this->getVersionFile($versionName);
        if (!file_exists($file)){
            return $result;
        }

        ob_start();
        /** @noinspection PhpIncludeInspection */
        require_once($file);
        ob_end_clean();

        $class = 'Sprint\Migration\\' . $versionName;
        if (!class_exists($class)) {
            return $result;
        }

        $descr = '';
        if (!method_exists($class, '__construct')){
            /** @var $versionInstance Version */
            $versionInstance = new $class;
            $descr = $versionInstance->getDescription();
        } elseif (class_exists('\ReflectionClass')){
            $reflect = new \ReflectionClass($class);
            $props = $reflect->getDefaultProperties();
            $descr = $props['description'];
        }

        $result['description'] = $this->prepareDescription($descr);
        $result['location'] = $this->getVersionFile($versionName);

        return $result;
    }

    public function createVersionFile($description = '', $prefix = '') {
        $description = $this->prepareDescription($description);
        $prefix = $this->preparePrefix($prefix);

        $originTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Moscow');
        $ts = date('YmdHis');
        date_default_timezone_set($originTz);

        $versionName = $prefix . $ts;

        list($extendUse, $extendClass) = explode(' as ', Module::getMigrationExtendClass());
        $extendUse = trim($extendUse);
        $extendClass = trim($extendClass);

        if (!empty($extendClass)){
            $extendUse = 'use ' . $extendUse . ' as ' .  $extendClass . ';' . PHP_EOL;
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

        /* @var $dbres \CDBResult */
        $dbres = $this->versionTable->getRecords();
        while ($aItem = $dbres->Fetch()) {
            $ts = $this->getVersionTimestamp($aItem['version']);
            if ($ts){
                $records[ $aItem['version'] ] = $ts;
            }
        }

        $files = array();

        /* @var $item \SplFileInfo */
        $directory = new \DirectoryIterator(Module::getMigrationDir());
        foreach ($directory as $item) {
            $fileName = pathinfo($item->getPathname(), PATHINFO_FILENAME);
            $ts = $this->getVersionTimestamp($fileName);
            if ($ts){
                $files[ $fileName ] = $ts;
            }
        }

        $merge = array_merge($records, $files);

        if ($for == 'down' || $for == 'unknown') {
            arsort($merge);
        } else {
            asort($merge);
        }

        $result = array();

        foreach ($merge as $version => $ts) {

            $isRecord = array_key_exists($version, $records);
            $isFile = array_key_exists($version, $files);

            if ($isRecord && $isFile) {
                $type = 'is_installed';
            } elseif (!$isRecord && $isFile) {
                $type = 'is_new';
            } else {
                $type = 'is_unknown';
            }

            if (($for == 'up' && $type == 'is_new') ||
                ($for == 'down' && $type == 'is_installed') ||
                ($for == 'unknown' && $type == 'is_unknown') ||
                ($for == 'all')){

                $result[] = array(
                    'type' => $type,
                    'version' => $version,
                );
            }

        }

        return $result;
    }

    public function getStatus(){
        $versions = $this->getVersions('all');

        $summ = array(
            'is_new' => 0,
            'is_installed' => 0,
            'is_unknown' => 0,
        );

        foreach ($versions as $aItem) {
            $type = $aItem['type'];
            $summ[$type]++;
        }

        return $summ;
    }

    public function checkPermissions($check = 1){
        $this->checkPerms = $check;
    }

    public function getVersionType($versionName) {
        if (!$this->checkVersionName($versionName)){
            return false;
        }

        $record = $this->versionTable->getRecordByName($versionName)->Fetch();
        $file = $this->getVersionFile($versionName);

        $isRecord = !empty($record);
        $isFile = file_exists($file);

        if (!$isRecord && !$isFile){
            return false;
        }

        if ($isRecord && $isFile) {
            $type = 'is_installed';
        } elseif (!$isRecord && $isFile) {
            $type = 'is_new';
        } else {
            $type = 'is_unknown';
        }

        return $type;
    }

    public function getVersionFile($versionName) {
        return Module::getMigrationDir() . '/'.$versionName . '.php';
    }

    public function checkVersionName($versionName){
        return $this->getVersionTimestamp($versionName) ? true : false;
    }

    public function getVersionTimestamp($versionName) {
        $matches = array();
        if (preg_match('/[0-9]{14}$/', $versionName, $matches)){
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

        if (is_file($file)){
            /** @noinspection PhpIncludeInspection */
            include $file;
        }

        $html = ob_get_clean();

        return $html;
    }


    protected function preparePrefix($prefix = ''){
        $prefix = trim($prefix);
        $default = 'Version';

        if (empty($prefix)){
            return $default;
        }

        $prefix = preg_replace("/[^a-z0-9_]/i", '', $prefix);
        if (empty($prefix)){
            return $default;
        }

        if (preg_match('/^\d/', $prefix)){
            return $default;
        }

        return $prefix;
    }

    protected function prepareDescription($descr = ''){
        $descr = strval($descr);
        $descr = nl2br( $descr);
        $descr = strip_tags($descr);
        $descr = addslashes($descr);
        return $descr;
    }

}
