<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exceptions\MigrationException;

use Sprint\Migration\Tables\VersionTable;

class VersionManager
{


    /** @var VersionConfig */
    private $versionConfig = null;

    /** @var VersionTable */
    private $versionTable = null;

    private $restarts = array();

    private $lastException = null;

    public function __construct($configName = '') {
        if ($configName instanceof VersionConfig) {
            $this->versionConfig = $configName;
        } else {
            $this->versionConfig = new VersionConfig(
                $configName
            );
        }

        $this->versionTable = new VersionTable(
            $this->getVersionConfig()->getVal('migration_table')
        );

        $this->lastException = new \Exception();
    }

    public function getVersionConfig() {
        return $this->versionConfig;
    }

    public function getVersionTable() {
        return $this->versionTable;
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
                $ok = $this->getVersionTable()->addRecord($meta);
            } else {
                $ok = $this->getVersionTable()->removeRecord($meta);
            }

            if ($ok === false) {
                throw new MigrationException('unable to write migration to the database');
            }

            return true;

        } catch (RestartException $e) {
            $this->restarts[$versionName] = isset($versionInstance) ? $versionInstance->getParams() : array();

        } catch (\Exception $e) {
            $this->lastException = $e;

        } catch (\Throwable $e) {
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
     * @param $name
     * @param $params
     * @return bool|AbstractBuilder
     */
    public function createBuilder($name, $params = array()) {
        $builders = $this->getVersionConfig()->getVal('version_builders', array());

        if (empty($builders[$name])) {
            return false;
        }

        $class = $builders[$name];

        if (!class_exists($class)) {
            return false;
        }

        /** @var  $builder AbstractBuilder */
        $builder = new $class($this->getVersionConfig(), $name, $params);

        if (!$builder->isEnabled()) {
            return false;
        }

        $builder->initializeBuilder();
        return $builder;
    }

    /**
     * @param array $filter
     * @return AbstractBuilder[]
     */
    public function createBuilders($filter = array()) {
        $group = !empty($filter['group']) ? $filter['group'] : 'default';

        $res = array();
        $builders = $this->getVersionConfig()->getVal('version_builders', array());
        foreach ($builders as $builderName => $builderClass) {
            if ($builder = $this->createBuilder($builderName)) {
                if ($builder->getGroup() == $group) {
                    $res[] = $builder;
                }
            }
        }
        return $res;
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
                $this->getVersionTable()->removeRecord($meta);
                $msg = 'SPRINT_MIGRATION_MARK_SUCCESS1';
                $success = true;
            } else {
                $msg = 'SPRINT_MIGRATION_MARK_ERROR1';
            }
        } elseif ($status == 'installed') {
            if (!$meta['is_record']) {
                $this->getVersionTable()->addRecord($meta);
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
            return $this->prepVersionMeta(
                $versionName,
                $this->getFileIfExists($versionName),
                $this->getRecordIfExists($versionName)
            );
        }
        return false;
    }

    public function getVersions($filter = array()) {
        /** @var  $versionFilter array */
        $versionFilter = $this->getVersionConfig()->getVal('version_filter', []);

        $filter = array_merge($versionFilter, array('status' => '', 'search' => ''), $filter);

        $merge = array();

        $records = $this->getRecords();
        $files = $this->getFiles();

        foreach ($records as $item) {
            $merge[$item['version']] = $item['ts'];
        }

        foreach ($files as $item) {
            $merge[$item['version']] = $item['ts'];
        }

        if ($filter['status'] == 'installed' || $filter['status'] == 'unknown') {
            arsort($merge);
        } else {
            asort($merge);
        }

        $result = array();
        foreach ($merge as $version => $ts) {
            $record = isset($records[$version]) ? $records[$version] : 0;
            $file = isset($files[$version]) ? $files[$version] : 0;

            $meta = $this->prepVersionMeta($version, $file, $record);

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

    protected function prepVersionMeta($versionName, $file, $record) {

        $isFile = ($file) ? 1 : 0;
        $isRecord = ($record) ? 1 : 0;

        $meta = array(
            'is_file' => $isFile,
            'is_record' => $isRecord,
            'version' => $versionName,
            'enabled' => true,
            'modified' => false,
            'hash' => ''
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

        $meta['location'] = $file['location'];

        ob_start();
        /** @noinspection PhpIncludeInspection */
        require_once($meta['location']);
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
        $meta['hash'] = md5(file_get_contents($meta['location']));

        if (!empty($record['hash'])) {
            $meta['modified'] = ($meta['hash'] != $record['hash']);
        }

        return $meta;

    }


    public function getVersionFile($versionName) {
        $dir = $this->getVersionConfig()->getVal('migration_dir');
        return $dir . '/' . $versionName . '.php';
    }

    protected function getFileIfExists($versionName) {
        $file = $this->getVersionFile($versionName);
        return file_exists($file) ? array(
            'version' => $versionName,
            'location' => $file,
        ) : 0;
    }

    protected function getRecordIfExists($versionName) {
        $record = $this->getVersionTable()->getRecord($versionName);
        return ($record && isset($record['version'])) ? $record : 0;
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

    public function getWebDir() {
        $dir = $this->getVersionConfig()->getVal('migration_dir');
        if (strpos($dir, Module::getDocRoot()) === 0) {
            return substr($dir, strlen(Module::getDocRoot()));
        }
        return '';
    }


    public function getRecords() {
        $result = array();

        $records = $this->getVersionTable()->getRecords();
        foreach ($records as $item) {
            if (empty($item['version'])) {
                continue;
            }

            $timestamp = $this->getVersionTimestamp($item['version']);
            if (!$timestamp) {
                continue;
            }

            $item['ts'] = $timestamp;

            $result[$item['version']] = $item;
        }

        return $result;
    }

    public function getFiles() {
        $dir = $this->getVersionConfig()->getVal('migration_dir');
        $files = array();

        /* @var $item \SplFileInfo */
        $items = new \DirectoryIterator($dir);
        foreach ($items as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $filename = pathinfo($item->getPathname(), PATHINFO_FILENAME);
            $timestamp = $this->getVersionTimestamp($filename);
            if (!$timestamp) {
                continue;
            }

            $files[$filename] = array(
                'version' => $filename,
                'location' => $item->getPathname(),
                'ts' => $timestamp
            );
        }

        return $files;
    }

    public function clean() {
        $dir = $this->getVersionConfig()->getVal('migration_dir');

        $files = $this->getFiles();
        foreach ($files as $meta) {
            unlink($meta['location']);
        }

        if (!empty($dir) && is_dir($dir)) {
            if (count(scandir($dir)) == 2) {
                rmdir($dir);
            }
        }

        $this->getVersionTable()
            ->deleteTable();
    }
}
