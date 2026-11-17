<?php

namespace Sprint\Migration;

use CMain;
use DirectoryIterator;
use SplFileInfo;
use Sprint\Migration\Entity\VersionEntity;
use Sprint\Migration\Entity\VersionFactory;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Output\OutputFactory;
use Sprint\Migration\Output\OutputInterface;
use Sprint\Migration\Results\VersionResult;
use Sprint\Migration\Tables\VersionTable;
use Throwable;

class VersionManager
{
    private VersionTable $versionTable;

    /**
     * @throws MigrationException
     */
    public function __construct(
        private VersionConfig    $versionConfig,
        private ?OutputInterface $output = null,
    )
    {
        $this->versionTable = new VersionTable(
            $this->versionConfig->getVal('migration_table')
        );

        $this->output ??= OutputFactory::create($this->versionConfig);
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
        string $action,
        array  $params,
        string $tag
    ): VersionResult
    {
        $result = new VersionResult();

        if (empty($params['~start'])) {
            $this->output->outInfo('%s::%s() start', $versionName, $action);
            $params['~start'] = 'ok';
        }

        try {
            $entity = $this->getVersionByName($versionName);

            if (!$entity->hasFile()) {
                throw new MigrationException('failed to initialize migration');
            }

            if ($entity->getOlderVersion()) {
                throw new MigrationException('unsupported version ' . $entity['older']);
            }

            if ($action == VersionEnum::ACTION_UP && $entity->getStatus() != VersionEnum::STATUS_NEW) {
                throw new MigrationException('migration already up');
            }

            if ($action == VersionEnum::ACTION_DOWN && $entity->getStatus() != VersionEnum::STATUS_INSTALLED) {
                throw new MigrationException('migration already down');
            }

            /** @var $versionObj Version */
            $versionObj = new ($entity->getFileClass());
            $versionObj->setVersionConfig($this->versionConfig);
            $versionObj->setOutput($this->output);
            $versionObj->setRestartParams($params);

            if ($action == VersionEnum::ACTION_UP) {
                $this->checkResultAfterStart($versionObj->up());
                $this->getVersionTable()->addRecord($versionName, $tag);
            } else {
                $this->checkResultAfterStart($versionObj->down());
                $this->getVersionTable()->removeRecord($versionName);
            }

        } catch (RestartException) {
            $result->setRestart(isset($versionObj) ? $versionObj->getRestartParams() : []);
        } catch (Throwable $e) {
            $result->setException($e);
            $this->output->outException($e);
        }

        if (!$result->needRestart()) {
            $this->output->outInfo('%s::%s() finish', $versionName, $action);
        }

        return $result;
    }

    /**
     * @throws MigrationException
     */
    public function getVersionByName(string $versionName): VersionEntity
    {
        $this->versionConfig->tryVersionName($versionName);

        return (new VersionFactory())->create(
            $this->versionConfig,
            $versionName,
            $this->getVersionTable()->getRecord($versionName)
        );
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
    public function getOnceForExecute(array $filter, string $action)
    {
        $filter['limit'] = 1;

        $names = $this->getListForExecute($filter, $action);

        return $names[0] ?? '';
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
        $mdir = $this->getVersionConfig()->getVal('migration_dir');
        $exdir = $this->getVersionConfig()->getVal('exchange_dir');
        $cfile = $this->getVersionConfig()->getPath();

        $this->getVersionTable()->dropTable();

        Module::deletePath($mdir);
        Module::deletePath($exdir);
        Module::deletePath($cfile);
    }

    /**
     * @throws MigrationException
     */
    public function deleteMigration(string $versionName): bool
    {
        $entity = $this->getVersionByName($versionName);

        $success = 0;
        if ($entity->hasRecord() && $this->getVersionTable()->removeRecord($versionName)) {
            $this->output->outInfo(Locale::getMessage('DELETE_RECORD', ['#VERSION#' => $versionName]));
            $success++;
        }

        if ($entity->hasFile()) {
            Module::deletePath($entity->getFileLocation());
            $this->output->outInfo(Locale::getMessage('DELETE_FILE', ['#VERSION#' => $versionName]));
            $success++;
        }

        if ($entity->hasExchangeDir()) {
            Module::deletePath($entity->getExchangeDir());
            $this->output->outInfo(Locale::getMessage('DELETE_EXCHANGE', ['#VERSION#' => $versionName]));
            $success++;
        }

        if ($success === 0) {
            $this->output->outWarning(Locale::getMessage('DELETE_ERROR', ['#VERSION#' => $versionName]));
        }

        return ($success > 0);
    }


    /**
     * @throws MigrationException
     */
    public function moveMigration(string $versionName, string $toConfig): bool
    {
        $vmTo = new VersionManager(
            ConfigManager::getInstance()->get($toConfig),
            $this->output
        );

        if ($this->getVersionConfig()->getName() == $vmTo->getVersionConfig()->getName()) {
            return false;
        }

        $success = 0;

        $entity = $this->getVersionByName($versionName);

        if ($entity->hasFile()) {
            Module::movePath(
                $entity->getFileLocation(),
                $vmTo->getVersionConfig()->getVersionFile($versionName)
            );
            $this->output->outInfo(Locale::getMessage('MOVE_FILE', ['#VERSION#' => $versionName]));
            $success++;
        }

        if ($entity->hasExchangeDir()) {
            Module::movePath(
                $entity->getExchangeDir(),
                $vmTo->getVersionConfig()->getVersionExchangeDir($versionName)
            );

            $this->output->outInfo(Locale::getMessage('MOVE_EXCANGE', ['#VERSION#' => $versionName]));
            $success++;
        }

        if ($entity->hasRecord()) {
            $this->getVersionTable()->removeRecord($versionName);
            $vmTo->getVersionTable()->addRecord($versionName);
            $this->output->outInfo(Locale::getMessage('MOVE_RECORD', ['#VERSION#' => $versionName]));
            $success++;
        }

        if ($success === 0) {
            $this->output->outWarning(Locale::getMessage('MOVE_EROR', ['#VERSION#' => $versionName]));
        }

        return ($success > 0);
    }

    /**
     * @throws MigrationException
     */
    public function markMigration(string $versionName, string $status): bool
    {
        $entity = $this->getVersionByName($versionName);

        $success = false;
        if ($status == VersionEnum::STATUS_NEW && $entity->hasRecord()) {
            $success = $this->getVersionTable()->removeRecord($versionName);
        }

        if ($status == VersionEnum::STATUS_INSTALLED && !$entity->hasRecord()) {
            $success = $this->getVersionTable()->addRecord($versionName);
        }

        if ($success) {
            $this->output->outInfo(Locale::getMessage("MARK_OK_$status", ['#VERSION#' => $versionName]));
        } else {
            $this->output->outWarning(Locale::getMessage('MARK_ERROR', ['#VERSION#' => $versionName]));
        }

        return $success;
    }

    /**
     * @throws MigrationException
     */
    public function setMigrationTag(string $versionName, string $tag): bool
    {
        $entity = $this->getVersionByName($versionName);

        $success = ($entity->hasRecord() && $this->getVersionTable()->updateTag($versionName, $tag));

        if ($success) {
            $this->output->outInfo(Locale::getMessage('SETTAG_OK', ['#VERSION#' => $versionName]));
        } else {
            $this->output->outWarning(Locale::getMessage('SETTAG_ERROR1', ['#VERSION#' => $versionName]));
        }
        return $success;
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
            if (!$this->getVersionConfig()->checkVersionName($versionName)) {
                throw new MigrationException(sprintf('Required "%s" not found', $versionName));
            }

            $record = $this->getVersionTable()->getRecord($versionName);
            if (empty($record)) {
                throw new MigrationException(sprintf('Required "%s" not installed', $versionName));
            }
        }
    }

}
