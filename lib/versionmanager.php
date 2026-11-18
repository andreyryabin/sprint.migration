<?php

namespace Sprint\Migration;

use CMain;
use DirectoryIterator;
use SplFileInfo;
use Sprint\Migration\Entity\VersionEntity;
use Sprint\Migration\Entity\VersionFactory;
use Sprint\Migration\Entity\VersionFile;
use Sprint\Migration\Entity\VersionFileFactory;
use Sprint\Migration\Entity\VersionRecord;
use Sprint\Migration\Entity\VersionRecordFactory;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Filters\VersionFilter;
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
        private readonly VersionConfig $versionConfig,
        private ?OutputInterface $output = null,
    ) {
        $this->versionTable = new VersionTable(
            $this->versionConfig->getVal('migration_table'),
        );

        $this->output ??= OutputFactory::create($this->versionConfig);
    }

    /**
     * @throws MigrationException
     */
    public function executeAll(array $filter, string $action, string $tag = ''): void
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

        foreach ($this->getVersions($filter) as $entity) {
            if (!$this->executeEntity($entity, $action, $tag)) {
                return;
            }
        }
    }

    /**
     * @return VersionEntity[]
     * @throws MigrationException
     */
    public function getVersions(array $params = []): array
    {
        $actualTs = $this->versionConfig->getActualTimestamp();
        $filter = VersionFilter::fromArray($params, $actualTs);

        $merge = [];

        $records = $this->getVersionRecords();
        $files = $this->getVersionFiles();

        foreach ($records as $versionName => $item) {
            $merge[$versionName] = $item->getVersionTimestamp();
        }

        foreach ($files as $versionName => $item) {
            $merge[$versionName] = $item->getVersionTimestamp();
        }

        if ($filter->isSortDesc()) {
            arsort($merge);
        } else {
            asort($merge);
        }

        $factory = new VersionFactory($this->versionConfig);

        $result = [];
        foreach ($merge as $versionName => $versionTimestamp) {
            $versionEntity = $factory->createFromObjects(
                $files[$versionName] ?? null,
                $records[$versionName] ?? null,
            );
            if ($filter->matches($versionEntity)) {
                $result[] = $versionEntity;
            }
        }

        return $result;
    }

    /**
     * @return VersionRecord[]
     * @throws MigrationException
     */
    private function getVersionRecords(): array
    {
        $factory = new VersionRecordFactory($this->versionConfig);

        $result = [];

        foreach ($this->getVersionTable()->getRecords() as $item) {
            if ($versionRecord = $factory->tryCreate($item)) {
                $result[$versionRecord->getVersionName()] = $versionRecord;
            }
        }

        return $result;
    }

    public function getVersionTable(): VersionTable
    {
        return $this->versionTable;
    }

    /**
     * @return VersionFile[]
     */
    private function getVersionFiles(): array
    {
        $dir = $this->versionConfig->getVal('migration_dir');

        $factory = new VersionFileFactory($this->versionConfig);

        /* @var $item SplFileInfo */
        $iterator = new DirectoryIterator($dir);

        $result = [];

        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getExtension() == 'php') {
                if ($versionFile = $factory->tryCreate($item->getBasename('.php'))) {
                    $result[$versionFile->getVersionName()] = $versionFile;
                }
            }
        }
        return $result;
    }

    private function executeEntity(VersionEntity $entity, string $action, string $tag = ''): bool
    {
        $params = [];

        do {
            $result = $this->startMigration(
                $entity,
                $action,
                $params,
                $tag,
            );

            $params = $result->getRestartParams();
        } while ($result->needRestart());

        return $result->isSuccess();
    }

    public function startMigration(
        VersionEntity $entity,
        string $action,
        array $params,
        string $tag,
    ): VersionResult {
        $result = new VersionResult();

        ob_start();
        try {
            if (empty($params['~start'])) {
                $this->output->outInfo('%s::%s() start', $entity->getVersionName(), $action);
                $params['~start'] = 'ok';
            }

            if (!$entity->hasFile()) {
                throw new MigrationException("failed to initialize migration");
            }

            if ($older = $entity->getOlderVersion()) {
                throw new MigrationException("unsupported version $older");
            }

            if ($action == VersionEnum::ACTION_UP && $entity->getStatus() != VersionEnum::STATUS_NEW) {
                throw new MigrationException("migration already up");
            }

            if ($action == VersionEnum::ACTION_DOWN && $entity->getStatus() != VersionEnum::STATUS_INSTALLED) {
                throw new MigrationException("migration already down");
            }

            $versionObj = $entity->createClassObject();
            $versionObj->setOutput($this->output);
            $versionObj->setVersionConfig($this->versionConfig);
            $versionObj->setRestartParams($params);

            if ($action == VersionEnum::ACTION_UP) {
                $this->checkResultAfterStart(
                    $versionObj->up(),
                );
                $this->getVersionTable()->addRecord(
                    $entity->getVersionName(),
                    $entity->getFileContentHash(),
                    $tag,
                );
            } else {
                $this->checkResultAfterStart(
                    $versionObj->down(),
                );
                $this->getVersionTable()->removeRecord(
                    $entity->getVersionName(),
                );
            }
        } catch (RestartException) {
            $result->setRestart(isset($versionObj) ? $versionObj->getRestartParams() : []);
        } catch (Throwable $e) {
            $result->setException($e);
        }

        if ($buffer = ob_get_clean()) {
            $this->output->out($buffer);
        }

        if ($result->getException()) {
            $this->output->outException($result->getException());
        }

        if (!$result->needRestart()) {
            $this->output->outInfo('%s::%s() finish', $entity->getVersionName(), $action);
        }

        return $result;
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
    public function executeVersion(string $versionName, string $action, string $tag = ''): bool
    {
        $entity = $this->getVersionByName($versionName);
        return $this->executeEntity($entity, $action, $tag);
    }

    /**
     * @throws MigrationException
     */
    public function getVersionByName(string $versionName): VersionEntity
    {
        $this->versionConfig->tryVersionName($versionName);

        $versionRecord = $this->getVersionTable()->getRecord($versionName);

        $factory = new VersionFactory($this->versionConfig);

        return $factory->createFromName($versionName, $versionRecord);
    }

    /**
     * @throws MigrationException
     */
    public function clean(): void
    {
        $mdir = $this->versionConfig->getVal('migration_dir');
        $exdir = $this->versionConfig->getVal('exchange_dir');
        $cfile = $this->versionConfig->getPath();

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
            $this->output,
        );

        if ($this->getVersionConfig()->getName() == $vmTo->getVersionConfig()->getName()) {
            return false;
        }

        $success = 0;

        $entity = $this->getVersionByName($versionName);

        if ($entity->hasFile()) {
            Module::movePath(
                $entity->getFileLocation(),
                $vmTo->getVersionConfig()->getVersionFile($versionName),
            );
            $this->output->outInfo(Locale::getMessage('MOVE_FILE', ['#VERSION#' => $versionName]));
            $success++;
        }

        if ($entity->hasExchangeDir()) {
            Module::movePath(
                $entity->getExchangeDir(),
                $vmTo->getVersionConfig()->getVersionExchangeDir($versionName),
            );

            $this->output->outInfo(Locale::getMessage('MOVE_EXCANGE', ['#VERSION#' => $versionName]));
            $success++;
        }

        if ($entity->hasRecord()) {
            $this->getVersionTable()->removeRecord(
                $versionName,
            );
            $vmTo->getVersionTable()->addRecord(
                $entity->getVersionName(),
                $entity->getFileContentHash(),
                $entity->getReleaseTag(),
            );
            $this->output->outInfo(Locale::getMessage('MOVE_RECORD', ['#VERSION#' => $versionName]));
            $success++;
        }

        if ($success === 0) {
            $this->output->outWarning(Locale::getMessage('MOVE_EROR', ['#VERSION#' => $versionName]));
        }

        return ($success > 0);
    }

    public function getVersionConfig(): VersionConfig
    {
        return $this->versionConfig;
    }

    /**
     * @throws MigrationException
     */
    public function markMigration(string $versionName, string $status): bool
    {
        $entity = $this->getVersionByName($versionName);

        $success = false;
        if ($status == VersionEnum::STATUS_NEW && $entity->hasRecord()) {
            $success = $this->getVersionTable()->removeRecord(
                $entity->getVersionName(),
            );
        }

        if ($status == VersionEnum::STATUS_INSTALLED && !$entity->hasRecord()) {
            $success = $this->getVersionTable()->addRecord(
                $entity->getVersionName(),
                $entity->getFileContentHash(),
                $entity->getReleaseTag(),
            );
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

}
