<?php

namespace Sprint\Migration;

use CMain;
use DirectoryIterator;
use Exception;
use ReflectionClass;
use SplFileInfo;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Tables\VersionTable;
use Throwable;

class VersionManager
{
    /** @var VersionConfig */
    private $versionConfig = null;
    /** @var VersionTable */
    private $versionTable = null;
    private $restarts = [];
    private $lastException = null;

    /**
     * VersionManager constructor.
     *
     * @param string $configName
     *
     * @throws Exception
     */
    public function __construct($configName = '')
    {
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

        $this->lastException = new Exception();
    }

    public function getVersionConfig()
    {
        return $this->versionConfig;
    }

    public function getVersionTable()
    {
        return $this->versionTable;
    }

    /**
     * @param        $versionName
     * @param string $action
     * @param array  $params
     * @param bool   $force
     * @param string $tag
     *
     * @return bool
     */
    public function startMigration(
        $versionName,
        $action = VersionEnum::ACTION_UP,
        $params = [],
        $force = false,
        $tag = ''
    ) {
        if (isset($this->restarts[$versionName])) {
            unset($this->restarts[$versionName]);
        }

        $this->lastException = new Exception();

        try {
            $meta = $this->getVersionByName($versionName);

            if (!$meta || empty($meta['class'])) {
                throw new MigrationException('failed to initialize migration');
            }

            if ($meta['older']) {
                throw new MigrationException('unsupported version ' . $meta['older']);
            }

            if (!$force) {
                if ($action == VersionEnum::ACTION_UP && $meta['status'] != VersionEnum::STATUS_NEW) {
                    throw new MigrationException('migration already up');
                }

                if ($action == VersionEnum::ACTION_DOWN && $meta['status'] != VersionEnum::STATUS_INSTALLED) {
                    throw new MigrationException('migration already down');
                }
            }

            /** @var $versionInstance Version */
            $versionInstance = new $meta['class'];

            $versionInstance->setRestartParams($params);

            if ($action == VersionEnum::ACTION_UP) {
                $this->checkResultAfterStart($versionInstance->up());

                $meta['tag'] = $tag;

                $this->getVersionTable()->addRecord($meta);
            } else {
                $this->checkResultAfterStart($versionInstance->down());

                $this->getVersionTable()->removeRecord($meta);
            }

            return true;
        } catch (RestartException $e) {
            $this->restarts[$versionName] = isset($versionInstance) ? $versionInstance->getRestartParams() : [];
        } catch (Exception $e) {
            $this->lastException = $e;
        } catch (Throwable $e) {
            $this->lastException = $e;
        }

        return false;
    }

    /**
     * @param $versionName
     *
     * @throws MigrationException
     * @return array|bool
     */
    public function getVersionByName($versionName)
    {
        if ($this->checkVersionName($versionName)) {
            return $this->prepVersionMeta(
                $versionName,
                $this->getFileIfExists($versionName),
                $this->getRecordIfExists($versionName)
            );
        }
        return false;
    }

    /**
     * @param array $filter
     *
     * @throws MigrationException
     * @return array
     */
    public function getVersions($filter = [])
    {
        /** @var  $versionFilter array */
        $versionFilter = $this->getVersionConfig()->getVal('version_filter', []);

        $filter = array_merge(
            $versionFilter, [
            'status'   => '',
            'search'   => '',
            'tag'      => '',
            'modified' => '',
            'older'    => '',
        ], $filter
        );

        $merge = [];

        $records = $this->getRecords();
        $files = $this->getFiles();

        foreach ($records as $item) {
            $merge[$item['version']] = $item['ts'];
        }

        foreach ($files as $item) {
            $merge[$item['version']] = $item['ts'];
        }

        if ($filter['status'] == VersionEnum::STATUS_INSTALLED || $filter['status'] == VersionEnum::STATUS_UNKNOWN) {
            arsort($merge);
        } else {
            asort($merge);
        }

        $result = [];
        foreach ($merge as $version => $ts) {
            $record = isset($records[$version]) ? $records[$version] : 0;
            $file = isset($files[$version]) ? $files[$version] : 0;

            $meta = $this->prepVersionMeta($version, $file, $record);

            if (
                $this->isVersionEnabled($meta)
                && $this->containsFilterStatus($meta, $filter)
                && $this->containsFilterSearch($meta, $filter)
                && $this->containsFilterTag($meta, $filter)
                && $this->containsFilterModified($meta, $filter)
                && $this->containsFilterOlder($meta, $filter)
                && $this->containsFilterVersion($meta, $filter)
            ) {
                $result[] = $meta;
            }
        }
        return $result;
    }

    public function needRestart($version)
    {
        return (isset($this->restarts[$version])) ? 1 : 0;
    }

    public function getRestartParams($version)
    {
        return $this->restarts[$version];
    }

    public function getLastException()
    {
        return $this->lastException;
    }

    /**
     * @param $name
     * @param $params
     *
     * @return bool|AbstractBuilder
     */
    public function createBuilder($name, $params = [])
    {
        $builders = $this->getVersionConfig()->getVal('version_builders', []);

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
     * @param $search
     * @param $status
     *
     * @throws MigrationException
     * @return array
     */
    public function markMigration($search, $status)
    {
        // $search - VersionName | new | installed | unknown
        // $status - new | installed

        $search = trim($search);
        $status = trim($status);

        $result = [];
        if (in_array(
            $status, [
            VersionEnum::STATUS_NEW,
            VersionEnum::STATUS_INSTALLED,
        ]
        )) {
            if ($this->checkVersionName($search)) {
                $meta = $this->getVersionByName($search);
                $meta = !empty($meta) ? $meta : ['version' => $search];
                $result[] = $this->markMigrationByMeta($meta, $status);
            } elseif (in_array(
                $search,
                [
                    VersionEnum::STATUS_NEW,
                    VersionEnum::STATUS_INSTALLED,
                    VersionEnum::STATUS_UNKNOWN,
                ]
            )) {
                $metas = $this->getVersions(['status' => $search]);
                foreach ($metas as $meta) {
                    $result[] = $this->markMigrationByMeta($meta, $status);
                }
            }
        }

        if (empty($result)) {
            $result[] = [
                'message' => Locale::getMessage('MARK_ERROR4'),
                'success' => false,
            ];
        }

        return $result;
    }

    public function getVersionFile($versionName)
    {
        $dir = $this->getVersionConfig()->getVal('migration_dir');
        return $dir . '/' . $versionName . '.php';
    }

    public function checkVersionName($versionName)
    {
        return $this->getVersionTimestamp($versionName) ? true : false;
    }

    public function getVersionTimestamp($versionName)
    {
        $matches = [];
        if (preg_match('/20\d{12}/', $versionName, $matches)) {
            return end($matches);
        }

        return false;
    }

    public function getWebDir()
    {
        $dir = $this->getVersionConfig()->getVal('migration_dir');
        if (strpos($dir, Module::getDocRoot()) === 0) {
            return substr($dir, strlen(Module::getDocRoot()));
        }
        return '';
    }

    /**
     * @return array
     */
    public function getRecords()
    {
        $result = [];

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

    public function getFiles()
    {
        $dir = $this->getVersionConfig()->getVal('migration_dir');
        $files = [];

        /* @var $item SplFileInfo */
        $items = new DirectoryIterator($dir);
        foreach ($items as $item) {
            if (!$item->isFile()) {
                continue;
            }

            if ($item->getExtension() != 'php') {
                continue;
            }

            $filename = pathinfo($item->getPathname(), PATHINFO_FILENAME);
            $timestamp = $this->getVersionTimestamp($filename);

            if (!$timestamp) {
                continue;
            }

            $files[$filename] = [
                'version'  => $filename,
                'location' => $item->getPathname(),
                'ts'       => $timestamp,
            ];
        }

        return $files;
    }

    public function clean()
    {
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

    /**
     * @param $versionName
     *
     * @throws MigrationException
     * @return array
     */
    public function deleteMigration($versionName)
    {
        $result = [];

        if (in_array(
            $versionName, [
            VersionEnum::STATUS_NEW,
            VersionEnum::STATUS_INSTALLED,
            VersionEnum::STATUS_UNKNOWN,
        ]
        )) {
            $metas = $this->getVersions(['status' => $versionName]);
        } elseif ($meta = $this->getVersionByName($versionName)) {
            $metas = [$meta];
        }

        if (!empty($metas)) {
            foreach ($metas as $meta) {
                $result[] = $this->deleteMigratioByMeta($meta);
            }
        } else {
            $result[] = [
                'message' => Locale::getMessage('DELETE_ERROR1'),
                'success' => 0,
            ];
        }

        return $result;
    }

    /**
     * @param        $versionName
     * @param string $tag
     *
     * @throws MigrationException
     * @return array
     */
    public function setMigrationTag($versionName, $tag = '')
    {
        $result = [];

        if (in_array(
            $versionName, [
            VersionEnum::STATUS_INSTALLED,
            VersionEnum::STATUS_UNKNOWN,
        ]
        )) {
            $metas = $this->getVersions(['status' => $versionName]);
        } elseif ($meta = $this->getVersionByName($versionName)) {
            $metas = [$meta];
        }

        if (!empty($metas)) {
            foreach ($metas as $meta) {
                $result[] = $this->setMigrationTagByMeta($meta, $tag);
            }
        } else {
            $result[] = [
                'message' => Locale::getMessage('SETTAG_ERROR1'),
                'success' => 0,
            ];
        }

        return $result;
    }

    /**
     * @param                $versionName
     * @param VersionManager $vmTo
     *
     * @throws MigrationException
     * @return array
     */
    public function transferMigration($versionName, VersionManager $vmTo)
    {
        $result = [];

        if ($this->getVersionConfig()->getName() == $vmTo->getVersionConfig()->getName()) {
            $result[] = [
                'message' => Locale::getMessage('TRANSFER_ERROR2'),
                'success' => 0,
            ];
            return $result;
        }

        if (in_array(
            $versionName, [
            VersionEnum::STATUS_NEW,
            VersionEnum::STATUS_INSTALLED,
            VersionEnum::STATUS_UNKNOWN,
        ]
        )) {
            $metas = $this->getVersions(['status' => $versionName]);
        } elseif ($versionName == 'all') {
            $metas = $this->getVersions([]);
        } elseif ($meta = $this->getVersionByName($versionName)) {
            $metas = [$meta];
        }

        if (!empty($metas)) {
            foreach ($metas as $meta) {
                $result[] = $this->transferMigrationByMeta($meta, $vmTo);
            }
        } else {
            $result[] = [
                'message' => Locale::getMessage('TRANSFER_ERROR1'),
                'success' => 0,
            ];
        }

        return $result;
    }

    /**
     * @param $meta
     * @param $status
     *
     * @return array
     */
    protected function markMigrationByMeta($meta, $status)
    {
        $msg = 'MARK_ERROR3';
        $success = false;

        if ($status == VersionEnum::STATUS_NEW) {
            if ($meta['is_record']) {
                $this->getVersionTable()->removeRecord($meta);
                if ($meta['is_file']) {
                    $msg = 'MARK_SUCCESS1';
                    $success = true;
                } else {
                    $msg = 'MARK_SUCCESS3';
                    $success = true;
                }
            } else {
                $msg = 'MARK_ERROR1';
            }
        } elseif ($status == VersionEnum::STATUS_INSTALLED) {
            if (!$meta['is_record']) {
                $this->getVersionTable()->addRecord($meta);
                $msg = 'MARK_SUCCESS2';
                $success = true;
            } else {
                $msg = 'MARK_ERROR2';
            }
        }

        return [
            'message' => Locale::getMessage($msg, ['#VERSION#' => $meta['version']]),
            'success' => $success,
        ];
    }

    protected function isVersionEnabled($meta)
    {
        return (isset($meta['enabled']) && $meta['enabled']);
    }

    protected function containsFilterVersion($meta, $filter)
    {
        unset($filter['status']);
        unset($filter['search']);
        unset($filter['tag']);
        unset($filter['modified']);
        unset($filter['older']);

        foreach ($filter as $k => $v) {
            if (empty($meta['version_filter'][$k]) || $meta['version_filter'][$k] != $v) {
                return false;
            }
        }

        return true;
    }

    protected function containsFilterTag($meta, $filter)
    {
        if (empty($filter['tag'])) {
            return true;
        }

        return ($meta['tag'] == $filter['tag']);
    }

    protected function containsFilterModified($meta, $filter)
    {
        if (empty($filter['modified'])) {
            return true;
        }

        return ($meta['modified']);
    }

    protected function containsFilterOlder($meta, $filter)
    {
        if (empty($filter['older'])) {
            return true;
        }

        return ($meta['older']);
    }

    protected function containsFilterSearch($meta, $filter)
    {
        if (empty($filter['search'])) {
            return true;
        }

        $textindex = $meta['version'] . $meta['description'] . $meta['tag'];
        $searchword = $filter['search'];

        $textindex = Locale::convertToUtf8IfNeed($textindex);
        $searchword = Locale::convertToUtf8IfNeed($searchword);

        $searchword = trim($searchword);

        if (false !== mb_stripos($textindex, $searchword, null, 'utf-8')) {
            return true;
        }

        return false;
    }

    protected function containsFilterStatus($meta, $filter)
    {
        if (empty($filter['status'])) {
            return true;
        }

        if ($filter['status'] == $meta['status']) {
            return true;
        }

        return false;
    }

    /**
     * @param $versionName
     * @param $file
     * @param $record
     *
     * @throws MigrationException
     * @return array|bool
     */
    protected function prepVersionMeta($versionName, $file, $record)
    {
        $isFile = ($file) ? 1 : 0;
        $isRecord = ($record) ? 1 : 0;

        $meta = [
            'is_file'   => $isFile,
            'is_record' => $isRecord,
            'version'   => $versionName,
            'enabled'   => true,
            'modified'  => false,
            'older'     => false,
            'hash'      => '',
            'tag'       => '',
        ];

        if ($isRecord && $isFile) {
            $meta['status'] = VersionEnum::STATUS_INSTALLED;
        } elseif (!$isRecord && $isFile) {
            $meta['status'] = VersionEnum::STATUS_NEW;
        } elseif ($isRecord && !$isFile) {
            $meta['status'] = VersionEnum::STATUS_UNKNOWN;
        } else {
            return false;
        }

        if ($isRecord) {
            $meta['tag'] = $record['tag'];
        }

        if (!$isFile) {
            return $meta;
        }

        $meta['location'] = $file['location'];

        try {
            /** @noinspection PhpIncludeInspection */
            require_once($file['location']);

            $class = 'Sprint\Migration\\' . $versionName;
            if (!class_exists($class)) {
                return $meta;
            }

            /** @var $versionInstance Version */
            $versionInstance = (new ReflectionClass($class))
                ->newInstanceWithoutConstructor();
            $meta['class'] = $class;
            $meta['description'] = $this->purifyDescriptionForMeta(
                $versionInstance->getDescription()
            );
            $meta['version_filter'] = $versionInstance->getVersionFilter();
            $meta['enabled'] = $versionInstance->isVersionEnabled();

            $v1 = $versionInstance->getModuleVersion();
            $v2 = Module::getVersion();

            if ($v1 && version_compare($v1, $v2, '>')) {
                $meta['older'] = $v1;
            }

            $meta['hash'] = md5(file_get_contents($meta['location']));
            if (!empty($record['hash'])) {
                $meta['modified'] = ($meta['hash'] != $record['hash']);
            }
        } catch (Exception $e) {
        } catch (Throwable $e) {
        }

        return $meta;
    }

    protected function getFileIfExists($versionName)
    {
        $file = $this->getVersionFile($versionName);
        return file_exists($file) ? [
            'version'  => $versionName,
            'location' => $file,
        ] : 0;
    }

    /**
     * @param $versionName
     *
     * @return array|false|int
     */
    protected function getRecordIfExists($versionName)
    {
        $record = $this->getVersionTable()->getRecord($versionName);
        return ($record && isset($record['version'])) ? $record : 0;
    }

    protected function purifyDescriptionForMeta($descr = '')
    {
        $descr = (string)$descr;
        $descr = str_replace(["\n\r", "\r\n", "\n", "\r"], ' ', $descr);
        $descr = strip_tags($descr);
        $descr = stripslashes($descr);
        return $descr;
    }

    protected function transferMigrationByMeta($meta, VersionManager $vmTo)
    {
        $success = 0;

        if ($meta['is_file']) {
            $source = $meta['location'];
            $dest = $vmTo->getVersionFile($meta['version']);

            if (is_file($dest)) {
                unlink($source);
            } else {
                rename($source, $dest);
            }

            $success = 1;
        }

        if ($meta['is_record']) {
            $this->getVersionTable()->removeRecord($meta);
            $vmTo->getVersionTable()->addRecord($meta);

            $success = 1;
        }

        return [
            'message' => Locale::getMessage('TRANSFER_OK', ['#VERSION#' => $meta['version']]),
            'success' => $success,
        ];
    }

    protected function deleteMigratioByMeta($meta)
    {
        $success = 0;

        if ($meta['is_record']) {
            $this->getVersionTable()->removeRecord($meta);
            $success = 1;
        }

        if ($meta && $meta['is_file']) {
            unlink($meta['location']);
            $success = 1;
        }

        $msg = $success ? 'DELETE_OK' : 'DELETE_ERROR2';

        return [
            'message' => Locale::getMessage($msg, ['#VERSION#' => $meta['version']]),
            'success' => $success,
        ];
    }

    protected function setMigrationTagByMeta($meta, $tag = '')
    {
        $success = 0;

        if ($meta['is_record']) {
            $this->getVersionTable()->updateTag($meta['version'], $tag);
            $success = 1;
        }

        $msg = $success ? 'SETTAG_OK' : 'SETTAG_ERROR2';

        return [
            'message' => Locale::getMessage($msg, ['#VERSION#' => $meta['version']]),
            'success' => $success,
        ];
    }

    /**
     * @param $ok
     *
     * @throws MigrationException
     */
    protected function checkResultAfterStart($ok)
    {
        /* @global $APPLICATION CMain */
        global $APPLICATION;

        if ($APPLICATION->GetException()) {
            throw new MigrationException($APPLICATION->GetException()->GetString());
        }

        if ($ok === false) {
            throw new MigrationException('migration return false');
        }
    }
}
