<?php

namespace Sprint\Migration;

use CMain;
use DateTime;
use DirectoryIterator;
use ReflectionClass;
use SplFileInfo;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Tables\VersionTable;
use Throwable;

class VersionManager
{
    private VersionConfig $versionConfig;
    private VersionTable  $versionTable;
    private bool          $isRestart         = false;
    private array         $lastRestartParams = [];
    private ?Throwable    $lastException;
    private string        $versionTimestampPattern;
    private string        $versionTimestampFormat;

    /**
     * @throws MigrationException
     */
    public function __construct($configName = '')
    {
        if ($configName instanceof VersionConfig) {
            $this->versionConfig = $configName;
        } else {
            $this->versionConfig = new VersionConfig($configName);
        }

        $this->versionTimestampPattern = $this
            ->versionConfig->getVal('version_timestamp_pattern');

        $this->versionTimestampFormat = $this
            ->versionConfig->getVal('version_timestamp_format');

        $this->versionTable = new VersionTable(
            $this->versionConfig->getVal('migration_table')
        );
    }

    public function getVersionConfig(): VersionConfig
    {
        return $this->versionConfig;
    }

    public function getVersionTable(): VersionTable
    {
        return $this->versionTable;
    }

    public function startMigration(
        string $versionName,
        string $action = VersionEnum::ACTION_UP,
        array $params = [],
        string $tag = ''
    ): bool {
        $this->isRestart = false;
        $this->lastRestartParams = [];
        $this->lastException = null;

        try {
            $meta = $this->getVersionByName($versionName);

            if (!$meta || empty($meta['class'])) {
                throw new MigrationException('failed to initialize migration');
            }

            if ($meta['older']) {
                throw new MigrationException('unsupported version ' . $meta['older']);
            }

            if (!empty($meta['required_versions'])) {
                if ($action == VersionEnum::ACTION_UP) {
                    $this->checkRequiredVersions($meta['required_versions']);
                }
            }

            if ($action == VersionEnum::ACTION_UP && $meta['status'] != VersionEnum::STATUS_NEW) {
                throw new MigrationException('migration already up');
            }

            if ($action == VersionEnum::ACTION_DOWN && $meta['status'] != VersionEnum::STATUS_INSTALLED) {
                throw new MigrationException('migration already down');
            }

            /** @var $versionInstance Version */
            $versionInstance = new $meta['class'];
            $versionInstance->setVersionConfig($this->versionConfig);
            $versionInstance->setRestartParams($params);

            if ($action == VersionEnum::ACTION_UP) {
                $this->checkResultAfterStart($versionInstance->up());

                $meta['tag'] = $tag;

                $this->getVersionTable()->addRecord($meta);
            } else {
                $this->checkResultAfterStart($versionInstance->down());

                $this->getVersionTable()->removeRecord($meta);
            }
        } catch (RestartException $e) {
            $this->isRestart = true;
            $this->lastRestartParams = isset($versionInstance) ? $versionInstance->getRestartParams() : [];
        } catch (Throwable $e) {
            $this->lastException = $e;
            return false;
        }

        return true;
    }

    /**
     * @param $versionName
     *
     * @throws MigrationException
     * @return array|bool
     */
    public function getVersionByName($versionName)
    {
        $ts = $this->getVersionTimestamp($versionName);

        if ($ts) {
            $fileName = $this->getVersionFile($versionName);
            $file = file_exists($fileName) ? [
                'version'  => $versionName,
                'location' => $fileName,
            ] : 0;

            $record = $this->getVersionTable()->getRecord($versionName);
            $record = !empty($record) ? $record : 0;

            return $this->makeVersion($versionName, $file, $record, $ts);
        }
        return false;
    }

    /**
     * @throws MigrationException
     */
    public function getVersions(array $filter = []): array
    {
        $filter = array_merge(['status' => ''], $filter);
        $merge = [];

        $records = $this->getRecords();
        $files = $this->getFiles();

        foreach ($records as $item) {
            $merge[$item['version']] = $item['ts'];
        }

        foreach ($files as $item) {
            $merge[$item['version']] = $item['ts'];
        }

        if ($filter['sort'] == VersionEnum::SORT_DESC) {
            arsort($merge);
        } else {
            asort($merge);
        }

        $result = [];
        $newFound = false;

        foreach ($merge as $version => $ts) {
            $record = $records[$version] ?? 0;
            $file = $files[$version] ?? 0;

            if ($filter['actual'] == 1) {
                if (!$newFound && $file && !$record) {
                    $newFound = true;
                }
                if (!$newFound) {
                    continue;
                }
            }

            $meta = $this->makeVersion($version, $file, $record, $ts);

            $check = (
                $this->containsFilterStatus($meta, $filter)
                && $this->containsFilterSearch($meta, $filter)
                && $this->containsFilterTag($meta, $filter)
                && $this->containsFilterModified($meta, $filter)
                && $this->containsFilterOlder($meta, $filter)
            );

            if (!$check) {
                continue;
            }

            $result[] = $meta;

            if ($filter['limit'] && count($result) == $filter['limit']) {
                break;
            }

        }

        return $result;
    }

    /**
     * @throws MigrationException
     */
    public function getListForExecute(array $filter, string $action): array
    {
        if ($action == VersionEnum::ACTION_UP) {
            $filter['status'] = VersionEnum::STATUS_NEW;
            $filter['sort'] = VersionEnum::SORT_ASC;
        } elseif ($action == VersionEnum::ACTION_DOWN) {
            $filter['status'] = VersionEnum::STATUS_INSTALLED;
            $filter['sort'] = VersionEnum::SORT_DESC;
        } else {
            throw new MigrationException("Migrate action \"$action\" not implemented");
        }

        return array_column($this->getVersions($filter), 'version');
    }

    /**
     * @throws MigrationException
     */
    public function getOnceForExecute(array $filter , string $action)
    {
        $filter['limit'] = 1;

        $names = $this->getListForExecute($filter,$action);

        return $names[0] ?? '';
    }

    public function needRestart(): bool
    {
        return $this->isRestart;
    }

    public function getRestartParams(): array
    {
        return $this->lastRestartParams;
    }

    public function getLastException(): ?Throwable
    {
        return $this->lastException;
    }

    /**
     * @throws BuilderException
     */
    public function createBuilder(string $name, array $params = []): AbstractBuilder
    {
        $builders = $this->getVersionConfig()->getVal('version_builders', []);

        $class = $builders[$name] ?? '';

        if ($class && class_exists($class)) {
            $builder = new $class($this->getVersionConfig(), $name, $params);
            if ($builder instanceof AbstractBuilder) {
                $builder->initializeBuilder();
                return $builder;
            }
        }

        throw new BuilderException(Locale::getMessage('ERR_BUILDER_NOT_FOUND', ['#NAME#' => $name]));
    }

    /**
     * @throws MigrationException
     */
    public function markMigration(string $search, string $status): array
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

    public function getVersionFile($versionName): string
    {
        $dir = $this->getVersionConfig()->getVal('migration_dir');
        return $dir . '/' . $versionName . '.php';
    }

    public function checkVersionName($versionName): bool
    {
        return (bool)$this->getVersionTimestamp($versionName);
    }

    public function getVersionTimestamp($versionName)
    {
        $matches = [];
        if (preg_match($this->versionTimestampPattern, $versionName, $matches)) {
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
     * @throws MigrationException
     */
    public function getRecords(): array
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

    public function getFiles(): array
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

        $this->getVersionTable()->deleteTable();
    }

    /**
     * @throws MigrationException
     */
    public function deleteMigration(string $versionName): array
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
                $result[] = $this->deleteMigrationByMeta($meta);
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
     * @throws MigrationException
     */
    public function setMigrationTag(string $versionName, string $tag = ''): array
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
     * @throws MigrationException
     */
    public function transferMigration(string $versionName, VersionManager $vmTo): array
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
            $items = $this->getVersions(['status' => $versionName]);
        } elseif ($versionName == 'all') {
            $items = $this->getVersions();
        } elseif ($meta = $this->getVersionByName($versionName)) {
            $items = [$meta];
        }

        if (!empty($items)) {
            foreach ($items as $meta) {
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
     * @throws MigrationException
     */
    protected function markMigrationByMeta(array $meta, string $status): array
    {
        $msg = 'MARK_ERROR3';
        $success = false;

        if ($status == VersionEnum::STATUS_NEW) {
            $msg = 'MARK_ERROR1';
            if ($meta['is_record']) {
                $success = true;
                $this->getVersionTable()->removeRecord($meta);
                if ($meta['is_file']) {
                    $msg = 'MARK_SUCCESS1';
                } else {
                    $msg = 'MARK_SUCCESS3';
                }
            }
        } elseif ($status == VersionEnum::STATUS_INSTALLED) {
            $msg = 'MARK_ERROR2';
            if (!$meta['is_record']) {
                $this->getVersionTable()->addRecord($meta);
                $msg = 'MARK_SUCCESS2';
                $success = true;
            }
        }

        return [
            'message' => Locale::getMessage($msg, ['#VERSION#' => $meta['version']]),
            'success' => $success,
        ];
    }

    protected function containsFilterTag($meta, $filter): bool
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

    protected function containsFilterSearch($meta, $filter): bool
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

    protected function containsFilterStatus($meta, $filter): bool
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
    protected function makeVersion($versionName, $file, $record, $ts)
    {
        $isFile = ($file) ? 1 : 0;
        $isRecord = ($record) ? 1 : 0;

        $meta = [
            'is_file'       => $isFile,
            'is_record'     => $isRecord,
            'version'       => $versionName,
            'modified'      => false,
            'older'         => false,
            'hash'          => '',
            'tag'           => '',
            'file_status'   => '',
            'record_status' => '',
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

            $meta['record_status'] = $this->humanStatus(
                Locale::getMessage('META_INSTALLED'),
                $record['meta']['created_at'] ?? '',
                $record['meta']['created_by'] ?? ''
            );
        }

        if (!$isFile) {
            return $meta;
        }

        $meta['location'] = realpath($file['location']);

        try {
            require_once($meta['location']);

            $class = __NAMESPACE__ . '\\' . $versionName;
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

            $humanTs = DateTime::createFromFormat($this->versionTimestampFormat, $ts);
            $meta['file_status'] = $this->humanStatus(
                Locale::getMessage('META_NEW'),
                $humanTs->format(VersionTable::DATE_FORMAT),
                $this->purifyDescriptionForMeta(
                    $versionInstance->getAuthor()
                )
            );

            $meta['required_versions'] = $versionInstance->getRequiredVersions();

            $v1 = $versionInstance->getModuleVersion();
            $v2 = Module::getVersion();

            if ($v1 && version_compare($v1, $v2, '>')) {
                $meta['older'] = $v1;
            }

            $algo = $this->getVersionConfig()->getVal('migration_hash_algo');

            $meta['hash'] = hash($algo, file_get_contents($meta['location']));
            $meta['modified'] = $record['hash'] && ($meta['hash'] != $record['hash']);
        } catch (Throwable $e) {
            throw new MigrationException($e->getMessage(), $e->getCode(), $e);
        }

        return $meta;
    }

    private function humanStatus($prefix, $at, $by)
    {
        $by = $by ? '(' . $by . ')' : '';

        if ($at && $by) {
            return $prefix . ': ' . $at . ' ' . $by;
        } elseif ($at || $by) {
            return $prefix . ': ' . $at . $by;
        }
        return $prefix;
    }

    protected function purifyDescriptionForMeta(string $descr = ''): string
    {
        return stripslashes(strip_tags(trim($descr)));
    }

    /**
     * @throws MigrationException
     */
    protected function transferMigrationByMeta(array $meta, VersionManager $vmTo): array
    {
        $success = 0;

        if ($meta['is_file']) {
            Module::movePath(
                $meta['location'],
                $vmTo->getVersionFile($meta['version'])
            );

            Module::movePath(
                $this->getVersionExchangeDir($meta['version']),
                $vmTo->getVersionExchangeDir($meta['version'])
            );

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

    /**
     * @throws MigrationException
     */
    protected function deleteMigrationByMeta($meta): array
    {
        $success = 0;

        if ($meta['is_record']) {
            $this->getVersionTable()->removeRecord($meta);
            $success = 1;
        }

        if ($meta && $meta['is_file']) {
            Module::deletePath(
                $meta['location']
            );
            Module::deletePath(
                $this->getVersionExchangeDir($meta['version'])
            );

            $success = 1;
        }

        $msg = $success ? 'DELETE_OK' : 'DELETE_ERROR2';

        return [
            'message' => Locale::getMessage($msg, ['#VERSION#' => $meta['version']]),
            'success' => $success,
        ];
    }

    public function getVersionExchangeDir($versionName): string
    {
        $dir = $this->getVersionConfig()->getVal('exchange_dir');
        return $dir . '/' . $versionName . '_files/';
    }

    /**
     * @throws MigrationException
     */
    protected function setMigrationTagByMeta($meta, $tag = ''): array
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

    /**
     * @throws MigrationException
     */
    public function checkRequiredVersions(array $versionNames)
    {
        foreach ($versionNames as $versionName) {
            if (strpos($versionName, '\\') !== false) {
                $versionName = substr(strrchr($versionName, '\\'), 1);
            }
            if (!$this->checkVersionName($versionName)) {
                throw new MigrationException(sprintf('Required "%s" not found', $versionName));
            }

            $record = $this->getVersionTable()->getRecord($versionName);
            if (empty($record)) {
                throw new MigrationException(sprintf('Required "%s" not installed', $versionName));
            }
        }
    }
}
