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
    private VersionTable $versionTable;
    private bool $isRestart = false;
    private array $lastRestartParams = [];
    private ?Throwable $lastException;

    /**
     * @throws MigrationException
     */
    public function __construct(private VersionConfig $versionConfig)
    {
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
        array  $params = [],
        string $tag = ''
    ): bool
    {
        $this->isRestart = false;
        $this->lastRestartParams = [];
        $this->lastException = null;

        try {
            $this->versionConfig->tryVersionName($versionName);

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
     * @throws MigrationException
     */
    public function getVersionByName(string $versionName): bool|array
    {
        if (!$this->versionConfig->checkVersionName($versionName)) {
            return false;
        }

        $fileName = $this->versionConfig->getVersionFile($versionName);
        $file = file_exists($fileName) ? [
            'version'  => $versionName,
            'location' => $fileName,
        ] : 0;

        $record = $this->getVersionTable()->getRecord($versionName);
        $record = !empty($record) ? $record : 0;

        $meta = $this->makeVersion(
            $versionName,
            $file,
            $record,
            $this->versionConfig->getVersionTimestamp($versionName)
        );

        $this->appendVersionLabels($meta);

        return $meta;

    }

    /**
     * @throws MigrationException
     */
    public function getVersions(array $filter = []): array
    {
        $view = $filter['view'] ?? '';

        $filter = [
            'status'   => $filter['status'] ?? '',
            'tag'      => $filter['tag'] ?? '',
            'search'   => $filter['search'] ?? '',
            'sort'     => $filter['sort'] ?? '',
            'actual'   => (int)($filter['actual'] ?? ''),
            'older'    => (int)($filter['older'] ?? ''),
            'modified' => (int)($filter['modified'] ?? ''),
            'limit'    => (int)($filter['limit'] ?? ''),
        ];

        if ($view == "new") {
            $filter['status'] = VersionEnum::STATUS_NEW;
        } elseif ($view == "installed") {
            $filter['status'] = VersionEnum::STATUS_INSTALLED;
        } elseif ($view == "unknown") {
            $filter['status'] = VersionEnum::STATUS_UNKNOWN;
        } elseif ($view == "actual") {
            $filter['actual'] = 1;
        } elseif ($view == "tag") {
            $filter['tag'] = $filter['search'] ?? '';
        } elseif ($view == "modified") {
            $filter['modified'] = 1;
        } elseif ($view == "older") {
            $filter['older'] = 1;
        }

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

        $actTs = date(
            $this->getVersionConfig()->getVal('version_timestamp_format'),
            time() - 8 * 3600
        );

        foreach ($merge as $version => $ts) {
            $record = $records[$version] ?? 0;
            $file = $files[$version] ?? 0;

            if ($filter['actual'] == 1) {
                if (!$newFound && $file && !$record) {
                    $newFound = true;
                }
                if (!$newFound && $ts < $actTs) {
                    continue;
                }
            }

            $meta = $this->makeVersion($version, $file, $record, $ts);

            $this->appendVersionLabels($meta);

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

    private function appendVersionLabels(array &$item)
    {
        $labels = [];
        if ($item['older']) {
            $labels[] = '[label:red]' . Locale::getMessage('OLDER_VERSION', ['#V1#' => $item['older']]) . '[/]';
        }
        if ($item['modified']) {
            $labels[] = '[label:yellow]' . Locale::getMessage('MODIFIED_VERSION') . '[/]';
        }
        if ($item['status'] == VersionEnum::STATUS_UNKNOWN) {
            $labels[] = '[label]' . Locale::getMessage('VERSION_UNKNOWN') . '[/]';
        }

        if ($item['tag']) {
            $labels[] = Locale::getMessage('RELEASE_TAG', [
                '#TAG#' => '[label:green]' . htmlspecialcharsbx($item['tag']) . '[/]',
            ]);
        }

        $item['labels'] = implode(' ', $labels);
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
    public function getOnceForExecute(array $filter, string $action)
    {
        $filter['limit'] = 1;

        $names = $this->getListForExecute($filter, $action);

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
    public function createBuilder(string $name, array $params = []): Builder
    {
        $builders = $this->getVersionConfig()->getVal('version_builders', []);

        $class = $builders[$name] ?? '';

        if ($class && class_exists($class)) {
            $builder = new $class($this->getVersionConfig(), $name, $params);
            if ($builder instanceof Builder) {
                $builder->buildInitialize();
                return $builder;
            }
        }

        throw new BuilderException(Locale::getMessage('ERR_BUILDER_NOT_FOUND', ['#NAME#' => $name]));
    }

    /**
     * @throws MigrationException
     */
    public function markMigration(string $versionName, string $status): bool
    {

        $this->versionConfig->tryVersionName($versionName);

        $meta = $this->getVersionByName($versionName);

        if ($status == VersionEnum::STATUS_NEW && $meta['is_record']) {
            return $this->getVersionTable()->removeRecord($meta);
        }

        if ($status == VersionEnum::STATUS_INSTALLED && !$meta['is_record']) {
            return $this->getVersionTable()->addRecord($meta);
        }

        return false;
    }

    /**
     * @throws MigrationException
     */
    public function getRecords(): array
    {
        $result = [];

        $records = $this->getVersionTable()->getRecords();
        foreach ($records as $item) {
            if (!$this->versionConfig->checkVersionName($item['version'])) {
                continue;
            }

            $timestamp = $this->versionConfig->getVersionTimestamp($item['version']);
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

            $versionName = pathinfo($item->getPathname(), PATHINFO_FILENAME);
            if (!$this->versionConfig->checkVersionName($versionName)) {
                continue;
            }

            $timestamp = $this->versionConfig->getVersionTimestamp($versionName);
            if (!$timestamp) {
                continue;
            }

            $files[$versionName] = [
                'version'  => $versionName,
                'location' => $item->getPathname(),
                'ts'       => $timestamp,
            ];
        }

        return $files;
    }

    /**
     * @throws MigrationException
     */
    public function clean(): void
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

        $this->getVersionTable()->dropTable();
    }

    /**
     * @throws MigrationException
     */
    public function deleteMigration(string $versionName): bool
    {
        $meta = $this->getVersionByName($versionName);

        $success = 0;

        if ($meta['is_record'] && $this->getVersionTable()->removeRecord($meta)) {
            $success++;
        }

        if ($meta['is_file']) {
            Module::deletePath(
                $meta['location']
            );
            Module::deletePath(
                $this->getVersionConfig()->getVersionExchangeDir($meta['version'])
            );

            $success++;
        }

        return ($success > 0);
    }

    /**
     * @throws MigrationException
     */
    public function setMigrationTag(string $versionName, string $tag = ''): bool
    {
        $meta = $this->getVersionByName($versionName);

        $success = 0;

        if ($meta['is_record'] && $this->getVersionTable()->updateTag($meta['version'], $tag)) {
            $success++;
        }

        return ($success > 0);
    }

    /**
     * @throws MigrationException
     */
    public function moveMigration(string $versionName, string $toConfig): bool
    {
        $vmTo = new VersionManager(ConfigManager::getInstance()->get($toConfig));

        if ($this->getVersionConfig()->getName() == $vmTo->getVersionConfig()->getName()) {
            return false;
        }

        $success = 0;

        $meta = $this->getVersionByName($versionName);

        if ($meta['is_file']) {
            Module::movePath(
                $meta['location'],
                $vmTo->getVersionConfig()->getVersionFile($meta['version'])
            );

            Module::movePath(
                $this->getVersionConfig()->getVersionExchangeDir($meta['version']),
                $this->getVersionConfig()->getVersionExchangeDir($meta['version'])
            );

            $success++;
        }

        if ($meta['is_record']) {
            $this->getVersionTable()->removeRecord($meta);
            $vmTo->getVersionTable()->addRecord($meta);

            $success++;
        }


        return ($success > 0);
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
        $searchword = trim($filter['search']);

        return (false !== mb_stripos($textindex, $searchword, null, 'utf-8'));
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
     * @throws MigrationException
     */
    protected function makeVersion(string $versionName, $file, $record, $ts): bool|array
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
            'labels'        => [],
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
            $meta['description'] = $this->stripslashes(
                $versionInstance->getDescription()
            );

            $humanTs = DateTime::createFromFormat(
                $this->versionConfig->getVal('version_timestamp_format'),
                $ts
            );
            $meta['file_status'] = $this->humanStatus(
                Locale::getMessage('META_NEW'),
                $humanTs->format(VersionTable::DATE_FORMAT),
                $this->stripslashes(
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

    private function humanStatus(string $prefix, string $at, string $by): string
    {
        $by = $by ? '(' . $by . ')' : '';

        if ($at && $by) {
            return $prefix . ': ' . $at . ' ' . $by;
        } elseif ($at || $by) {
            return $prefix . ': ' . $at . $by;
        }
        return $prefix;
    }

    protected function stripslashes(string $descr = ''): string
    {
        return stripslashes(strip_tags(trim($descr)));
    }

    /**
     * @throws MigrationException
     */
    protected function checkResultAfterStart($ok): void
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
    public function checkRequiredVersions(array $versionNames): void
    {
        foreach ($versionNames as $versionName) {
            if (str_contains($versionName, '\\')) {
                $versionName = substr(strrchr($versionName, '\\'), 1);
            }
            if (!$this->versionConfig->checkVersionName($versionName)) {
                throw new MigrationException(sprintf('Required "%s" not found', $versionName));
            }

            $record = $this->getVersionTable()->getRecord($versionName);
            if (empty($record)) {
                throw new MigrationException(sprintf('Required "%s" not installed', $versionName));
            }
        }
    }

}
