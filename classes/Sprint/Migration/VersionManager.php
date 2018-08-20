<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exceptions\MigrationException;

class VersionManager
{


    /** @var VersionConfig */
    private $versionConfig = null;

    /** @var VersionTable */
    private $versionTable = null;

    private $restarts = array();

    private $lastException = null;

    public function __construct($configName = '') {
        $this->versionConfig = new VersionConfig(
            $configName
        );

        $this->versionTable = new VersionTable(
            $this->getConfigVal('migration_table')
        );

        $this->lastException = new \Exception();
    }

    public function startMigration($versionName, $action = 'up', $params = array(), $force = false) {
        /* @global $APPLICATION \CMain */
        global $APPLICATION;

        if (isset($this->restarts[$versionName])) {
            unset($this->restarts[$versionName]);
        }

        $this->lastException = new \Exception();

        try {

            $action = ($action == 'up') ? 'up' : 'down';

            $meta = $this->getVersionByName($versionName);

            if (!$meta || empty($meta['class'])) {
                throw new MigrationException('failed to initialize migration');
            }

            if (!$force) {
                if ($action == 'up' && $meta['status'] != 'new') {
                    throw new MigrationException('migration already up');
                }

                if ($action == 'down' && $meta['status'] != 'installed') {
                    throw new MigrationException('migration already down');
                }
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
                $ok = $this->addRecord($versionName);
            } else {
                $ok = $this->removeRecord($versionName);
            }

            if ($ok === false) {
                throw new MigrationException('unable to write migration to the database');
            }

            return true;

        } catch (RestartException $e) {
            $this->restarts[$versionName] = isset($versionInstance) ? $versionInstance->getParams() : array();

        } catch (\Exception $e) {
            $this->lastException = $e;
        }

        return false;
    }


    public function needRestart($version) {
        return (isset($this->restarts[$version])) ? 1 : 0;
    }

    public function getRestartParams($version) {
        return $this->restarts[$version];
    }

    public function getLastException() {
        return $this->lastException;
    }

    /**
     * @param string $name
     * @param array $postvars
     * @return AbstractBuilder
     */
    public function createVersionBuilder($name = '', $postvars = array()) {
        $builders = $this->getVersionBuilders();

        if (isset($builders[$name]) && class_exists($builders[$name])) {
            $class = $builders[$name];
        } else {
            $class = $builders['Version'];
        }

        $builder = new $class(
            $this->versionConfig,
            $name,
            $postvars
        );

        return $builder;
    }

    public function isVersionBuilder($name = '') {
        $builders = $this->getVersionBuilders();
        return (isset($builders[$name]) && class_exists($builders[$name]));
    }


    public function getVersionBuilders() {
        $builders = $this->getConfigVal('version_builders', array());
        return is_array($builders) ? $builders : array();
    }

    public function markMigration($search, $status) {
        // $search - VersionName | new | installed | unknown
        // $status - new | installed

        $search = trim($search);
        $status = trim($status);

        $result = array();
        if (in_array($status, array('new', 'installed'))) {
            if ($this->checkVersionName($search)) {
                $meta = $this->getVersionByName($search);
                $meta = !empty($meta) ? $meta : array('version' => $search);
                $result[] = $this->markMigrationByMeta($meta, $status);

            } elseif (in_array($search, array('new', 'installed', 'unknown'))) {
                $metas = $this->getVersions(array('status' => $search));
                foreach ($metas as $meta) {
                    $result[] = $this->markMigrationByMeta($meta, $status);
                }
            }
        }

        if (empty($result)) {
            $result[] = array(
                'message' => GetMessage('SPRINT_MIGRATION_MARK_ERROR4'),
                'success' => false,
            );
        }

        return $result;
    }

    protected function markMigrationByMeta($meta, $status) {
        $msg = 'SPRINT_MIGRATION_MARK_ERROR3';
        $success = false;

        if ($status == 'new') {
            if ($meta['is_record']) {
                $this->removeRecord($meta['version']);
                $msg = 'SPRINT_MIGRATION_MARK_SUCCESS1';
                $success = true;
            } else {
                $msg = 'SPRINT_MIGRATION_MARK_ERROR1';
            }
        } elseif ($status == 'installed') {
            if (!$meta['is_record']) {
                $this->addRecord($meta['version']);
                $msg = 'SPRINT_MIGRATION_MARK_SUCCESS2';
                $success = true;
            } else {
                $msg = 'SPRINT_MIGRATION_MARK_ERROR2';
            }
        }

        return array(
            'message' => GetMessage($msg, array('#VERSION#' => $meta['version'])),
            'success' => $success,
        );
    }

    public function getVersionByName($versionName) {
        if ($this->checkVersionName($versionName)) {
            $isRecord = $this->isRecordExists($versionName);
            $isFile = $this->isFileExists($versionName);
            return $this->prepVersionMeta($versionName, $isFile, $isRecord);
        }
        return false;
    }

    public function getVersions($filter = array()) {
        /** @var  $versionFilter array */
        $versionFilter = $this->getConfigVal('version_filter', []);

        $filter = array_merge($versionFilter, array('status' => '', 'search' => ''), $filter);

        $records = array();
        /* @var $dbres \CDBResult */
        $dbres = $this->getRecords();
        while ($aItem = $dbres->Fetch()) {
            $ts = $this->getVersionTimestamp($aItem['version']);
            if ($ts) {
                $records[$aItem['version']] = $ts;
            }
        }

        $files = array();
        /* @var $item \SplFileInfo */
        $directory = new \DirectoryIterator($this->getConfigVal('migration_dir'));
        foreach ($directory as $item) {
            if ($item->isFile()) {
                $fileName = pathinfo($item->getPathname(), PATHINFO_FILENAME);
                $ts = $this->getVersionTimestamp($fileName);
                if ($ts) {
                    $files[$fileName] = $ts;
                }
            }

        }

        $merge = array_merge($records, $files);

        if ($filter['status'] == 'installed' || $filter['status'] == 'unknown') {
            arsort($merge);
        } else {
            asort($merge);
        }

        $result = array();
        foreach ($merge as $version => $ts) {
            $isRecord = array_key_exists($version, $records);
            $isFile = array_key_exists($version, $files);

            $meta = $this->prepVersionMeta($version, $isFile, $isRecord);

            if (
                $this->isVersionEnabled($meta) &&
                $this->containsFilterStatus($meta, $filter) &&
                $this->containsFilterSearch($meta, $filter) &&
                $this->containsFilterVersion($meta, $filter)
            ) {
                $result[] = $meta;
            }

        }
        return $result;
    }

    protected function isVersionEnabled($meta) {
        return (isset($meta['enabled']) && $meta['enabled']);
    }

    protected function containsFilterVersion($meta, $filter) {
        unset($filter['status']);
        unset($filter['search']);

        foreach ($filter as $k => $v) {
            if (empty($meta['versionfilter'][$k]) || $meta['versionfilter'][$k] != $v) {
                return false;
            }
        }

        return true;
    }

    protected function containsFilterSearch($meta, $filter) {
        if (empty($filter['search'])) {
            return true;
        }

        $textindex = $meta['version'] . $meta['description'];
        $searchword = $filter['search'];

        $textindex = Locale::convertToUtf8IfNeed($textindex);
        $searchword = Locale::convertToUtf8IfNeed($searchword);

        $searchword = trim($searchword);

        if (false !== mb_stripos($textindex, $searchword, null, 'utf-8')) {
            return true;
        }

        return false;
    }

    protected function containsFilterStatus($meta, $filter) {
        if (empty($filter['status'])) {
            return true;
        }

        if ($filter['status'] == $meta['status']) {
            return true;
        }

        return false;
    }


    protected function prepVersionMeta($versionName, $isFile, $isRecord) {
        $meta = array(
            'is_file' => $isFile,
            'is_record' => $isRecord,
            'version' => $versionName,
            'enabled' => true,
        );

        if ($isRecord && $isFile) {
            $meta['status'] = 'installed';
        } elseif (!$isRecord && $isFile) {
            $meta['status'] = 'new';
        } elseif ($isRecord && !$isFile) {
            $meta['status'] = 'unknown';
        } else {
            return false;
        }

        if (!$isFile) {
            return $meta;
        }

        $file = $this->getVersionFile($versionName);
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
        $filter = [];
        $enabled = true;
        if (!method_exists($class, '__construct')) {
            /** @var $versionInstance Version */
            $versionInstance = new $class;
            $descr = $versionInstance->getDescription();
            $filter = $versionInstance->getVersionFilter();
            $enabled = $versionInstance->isVersionEnabled();
        } elseif (class_exists('\ReflectionClass')) {
            $reflect = new \ReflectionClass($class);
            $props = $reflect->getDefaultProperties();
            $descr = $props['description'];
            $filter = $props['versionfilter'];
        }

        $meta['class'] = $class;
        $meta['description'] = $this->purifyDescriptionForMeta($descr);
        $meta['versionfilter'] = $filter;
        $meta['enabled'] = $enabled;

        return $meta;

    }


    protected function getVersionFile($versionName) {
        return $this->getConfigVal('migration_dir') . '/' . $versionName . '.php';
    }

    protected function isFileExists($versionName) {
        $file = $this->getVersionFile($versionName);
        return file_exists($file) ? 1 : 0;
    }

    protected function isRecordExists($versionName) {
        $record = $this->getRecord($versionName)->Fetch();
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

    protected function purifyDescriptionForMeta($descr = '') {
        $descr = strval($descr);
        $descr = str_replace(array("\n\r", "\r\n", "\n", "\r"), ' ', $descr);
        $descr = strip_tags($descr);
        $descr = stripslashes($descr);
        return $descr;
    }


    //config
    public function getConfigName() {
        return $this->versionConfig->getConfigName();
    }

    public function getConfigVal($val, $default = '') {
        return $this->versionConfig->getConfigVal($val, $default);
    }

    public function getWebDir() {
        $dir = $this->getConfigVal('migration_dir', '');
        if (strpos($dir, Module::getDocRoot()) === 0) {
            return substr($dir, strlen(Module::getDocRoot()));
        }
        return '';
    }

    public function getConfigList() {
        return $this->versionConfig->getConfigList();
    }

    public function getConfigCurrent() {
        return $this->versionConfig->getConfigCurrent();
    }

    //table
    protected function getRecords() {
        return $this->versionTable->getRecords();
    }

    protected function getRecord($versionName) {
        return $this->versionTable->getRecord($versionName);
    }

    protected function addRecord($versionName) {
        return $this->versionTable->addRecord($versionName);
    }

    protected function removeRecord($versionName) {
        return $this->versionTable->removeRecord($versionName);
    }
}
