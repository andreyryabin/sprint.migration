<?php

namespace Sprint\Migration\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Request;
use Sprint\Migration\ConfigManager;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Output\BrowserOutput;
use Sprint\Migration\VersionManager;
use Throwable;

/**
 * @noinspection PhpUnused
 * controller: sprint:migration.controller.versioncontroller
 */
class VersionController extends Controller
{
    protected VersionManager $versionManager;
    protected BrowserOutput $output;

    /**
     * @throws MigrationException
     */
    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $configManager = ConfigManager::getInstance();

        $versionConfig = $configManager->get(
            $this->getRequest()->get('config')
        );

        $this->output = new BrowserOutput($versionConfig->getLogger());

        $this->versionManager = new VersionManager(
            $versionConfig,
            $this->output
        );
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
        $versions = $this->versionManager->getVersions($filter);

        return ['versions' => $versions];
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */

    public function markVersionAction(string $version, string $status): array
    {
        $this->versionManager->markMigration($version, $status);

        return array_merge([
            'meta' => $this->versionManager->getVersionByName($version)
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

        return array_merge([
            'meta' => $this->versionManager->getVersionByName($version)
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

        return array_merge([
            'success' => $result->isSuccess(),
            'restart' => $result->needRestart(),
            'params'  => $result->getRestartParams(),
            'meta'    => $this->versionManager->getVersionByName($version)
        ], $this->output->getLog());
    }
}
