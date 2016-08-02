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

            $meta = $this->getVersionMeta($versionName);

            if (!$meta || empty($meta['class'])) {
                throw new MigrationException('failed to initialize migration');
            }

            if ($this->checkPerms) {
                if ($meta['type'] == 'is_unknown') {
                    throw new MigrationException('migration not found');
                }

                if ($action == 'up' && $meta['type'] != 'is_new') {
                    throw new MigrationException('migration already up');
                }

                if ($action == 'down' && $meta['type'] != 'is_installed') {
                    throw new MigrationException('migration already down');
                }
            }

            if (isset($this->restarts[$versionName])) {
                unset($this->restarts[$versionName]);
            } else {
                Out::outToConsoleOnly('%s (%s) start', $versionName, $action);
            }

            /** @var $versionInstance Version */
            $versionInstance = new $meta['class'];
            $versionInstance->setParams($params);

            if ($action == 'up') {
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

            if ($action == 'up') {
                $ok = $this->versionTable->addRecord($versionName);
            } else {
                $ok = $this->versionTable->removeRecord($versionName);
            }

            if ($ok === false) {
                throw new MigrationException('unable to write migration to the database');
            }

            Out::outToConsoleOnly('%s (%s) success', $versionName, $action);
            return true;

        } catch (RestartException $e) {
            $this->restarts[$versionName] = isset($versionInstance) ? $versionInstance->getParams() : array();

        } catch (MigrationException $e) {
            Out::outError('%s (%s) error: %s', $versionName, $action, $e->getMessage());

        } catch (\Exception $e) {
            Out::outError('%s (%s) error: %s', $versionName, $action, $e->getMessage());
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


    public function getVersions($for = 'all') {
        $for = in_array($for, array('all', 'up', 'down', 'unknown')) ? $for : 'all';

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
        if ($for == 'down' || $for == 'unknown') {
            arsort($merge);
        } else {
            asort($merge);
        }

        $result = array();
        foreach ($merge as $version => $ts) {
            $isRecord = array_key_exists($version, $records);
            $isFile = array_key_exists($version, $files);

            $meta = $this->prepVersionMeta($version, $isFile, $isRecord);

            if (($for == 'up' && $meta['type'] == 'is_new') ||
                ($for == 'down' && $meta['type'] == 'is_installed') ||
                ($for == 'unknown' && $meta['type'] == 'is_unknown') ||
                ($for == 'all')
            ) {
                $result[] = $meta;
            }
        }
        return $result;
    }

    protected function prepVersionMeta($versionName, $isFile, $isRecord) {
        $file = $this->getVersionFile($versionName);

        $meta = array(
            'version' => $versionName,
            'location' => $file,
            'is_file' => $isFile,
            'is_record' => $isRecord,
        );

        if ($isRecord && $isFile) {
            $meta['type'] = 'is_installed';
        } elseif (!$isRecord && $isFile) {
            $meta['type'] = 'is_new';
        } else {
            $meta['type'] = 'is_unknown';
        }

        if (!$isFile) {
            return $meta;
        }

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
        $meta['description'] = $this->prepareDescription($descr);

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

    public function getStatus() {
        $versions = $this->getVersions('all');
        $summ = array();
        foreach ($versions as $aItem) {
            $type = $aItem['type'];
            if (!isset($summ[$type])) {
                $summ[$type] = 0;
            }

            $summ[$type]++;
        }
        return $summ;
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


    protected function preparePrefix($prefix = '') {
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

    protected function prepareDescription($descr = '') {
        $descr = strval($descr);
        $descr = str_replace(array("\n\r", "\r\n", "\n","\r"), '<br />', $descr );
        $descr = strip_tags($descr);
        $descr = addslashes($descr);
        return $descr;
    }

}
