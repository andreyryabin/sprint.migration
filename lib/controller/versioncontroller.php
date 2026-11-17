<?php

namespace Sprint\Migration\Controller;

use Bitrix\Main\Engine\Action;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use CMain;
use Sprint\Migration\ConfigManager;
use Sprint\Migration\Entity\VersionEntity;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\Output\RestOutput;
use Sprint\Migration\VersionManager;
use Throwable;

/**
 * @noinspection PhpUnused
 * controller: sprint:migration.controller.versioncontroller
 */
class VersionController extends Controller
{
    protected VersionManager $versionManager;
    protected RestOutput $output;

    /**
     * @throws MigrationException
     */
    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        Loc::setCurrentLang(
            $this->getRequest()->get('lang') ?? Locale::getDefaultLang()
        );

        $versionConfig = ConfigManager::getInstance()->get(
            $this->getRequest()->get('config')
        );

        $this->output = new RestOutput(
            $versionConfig->getLogger()
        );

        $this->versionManager = new VersionManager(
            $versionConfig,
            $this->output
        );
    }

    protected function processBeforeAction(Action $action): bool
    {
        if (CMain::GetUserRight(Module::ID) == 'D') {
            $this->addError(new Error('ACCESS_DENIED'));
            return false;
        }

        return parent::processBeforeAction($action);
    }

    public function configureActions(): array
    {
        return [];
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function refreshVersionsAction(array $filter = []): array
    {
        $versions = array_map(
            fn(VersionEntity $entity) => $this->versionToArray($entity),
            $this->versionManager->getVersions($filter)
        );

        return ['versions' => $versions];
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function markVersionAction(string $version, string $status): array
    {
        $this->versionManager->markMigration($version, $status);
        $entity = $this->versionManager->getVersionByName($version);

        return array_merge([
            'meta' => $this->versionToArray($entity)
        ], $this->output->getLog());
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function deleteVersionAction(string $version): array
    {
        $this->versionManager->deleteMigration($version);

        return $this->output->getLog();
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function moveVersionAction(string $version, string $toConfig): array
    {
        $this->versionManager->moveMigration($version, $toConfig);

        return $this->output->getLog();
    }


    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function moveAllAction(array $versions, string $toConfig): array
    {
        foreach ($versions as $versionName) {
            $this->versionManager->moveMigration($versionName, $toConfig);
        }
        return $this->output->getLog();
    }


    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function setVersionTagAction(string $version, string $settag): array
    {
        $this->versionManager->setMigrationTag($version, $settag);
        $entity = $this->versionManager->getVersionByName($version);

        return array_merge([
            'meta' => $this->versionToArray($entity)
        ], $this->output->getLog());
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function setAllTagAction(array $versions, string $settag): array
    {
        foreach ($versions as $versionName) {
            $this->versionManager->setMigrationTag($versionName, $settag);
        }
        return $this->output->getLog();
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     * @throws Throwable
     */
    public function execVersionAction(string $version, string $action, array $params = [], string $settag = ''): array
    {
        $action = ($action === VersionEnum::ACTION_DOWN) ? VersionEnum::ACTION_DOWN : VersionEnum::ACTION_UP;

        $result = $this->versionManager->startMigration(
            $version,
            $action,
            $params,
            $settag
        );

        $entity = $this->versionManager->getVersionByName($version);

        return array_merge([
            'success' => $result->isSuccess(),
            'restart' => $result->needRestart(),
            'params'  => $result->getRestartParams(),
            'meta'    => $this->versionToArray($entity)
        ], $this->output->getLog());
    }


    /**
     * @throws MigrationException
     */
    private function versionToArray(VersionEntity $entity): array
    {
        $meta = [
            'version' => $entity->getVersionName(),
            'status'  => $entity->getStatus(),
            'labels'  => $entity->getLabels(),
        ];

        if ($entity->hasRecord()) {
            $meta['tag'] = $entity->getReleaseTag();
            $meta['tag_status'] = $entity->getTagStatus();
            $meta['record_status'] = $entity->getRecordStatus();
        }

        if ($entity->hasFile()) {
            $meta['file_status'] = $entity->getFileStatus();
            $meta['description'] = $entity->getFileDescription();
        }

        return $meta;
    }
}
