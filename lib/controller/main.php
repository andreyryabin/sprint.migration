<?php

namespace Sprint\Migration\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Request;
use Sprint\Migration\Builder;
use Sprint\Migration\ConfigManager;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Output\BrowserOutput;
use Sprint\Migration\Output\CompositeOutput;
use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;
use Throwable;

/**
 * @noinspection PhpUnused
 * controller: sprint:migration.controller.main
 */
class Main extends Controller
{
    protected VersionManager $versionManager;
    protected BrowserOutput $browserOutput;
    private VersionConfig $versionConfig;

    /**
     * @throws MigrationException
     */
    public function __construct(Request $request = null)
    {
        parent::__construct($request);

        $configManager = ConfigManager::getInstance();

        $this->versionConfig = $configManager->get(
            $this->getRequest()->get('config')
        );

        $this->browserOutput = new BrowserOutput();

        $this->versionManager = new VersionManager(
            $this->versionConfig,
            new CompositeOutput(
                $this->browserOutput,
                $this->versionConfig->getLogger()
            )
        );
    }

    public function configureActions(): array
    {
        $prefilters = [
            new ActionFilter\HttpMethod([ActionFilter\HttpMethod::METHOD_POST]),
            new ActionFilter\Authentication(),
            new ActionFilter\Csrf(),
        ];

        return [
            'refresh' => [
                'prefilters' => $prefilters
            ],
        ];
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
     */
    public function refreshBuildersAction(): array
    {
        $builders = array_map(fn(Builder $builder) => [
            'name'  => $builder->getName(),
            'title' => $builder->getTitle(),
            'group' => $builder->getGroup(),
        ], $this->versionManager->getBuilders());

        return ['builders' => $builders];
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
        ], $this->browserOutput->getLog());
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function deleteVersionAction(string $version): array
    {
        $this->versionManager->deleteMigration($version);

        return $this->browserOutput->getLog();
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function moveVersionAction(string $version, string $toConfig): array
    {
        $this->versionManager->moveMigration($version, $toConfig);

        return $this->browserOutput->getLog();
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
        ], $this->browserOutput->getLog());
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function setAllTagAction(array $versions, string $settag): array
    {
        foreach ($versions as $version) {
            $this->versionManager->setMigrationTag($version, $settag);
        }
        return $this->browserOutput->getLog();
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     * @throws Throwable
     */
    public function execVersionAction(string $version, string $action, array $params = [], string $settag = ''): array
    {
        $action = ($action === VersionEnum::ACTION_DOWN) ? VersionEnum::ACTION_DOWN : VersionEnum::ACTION_UP;

        $success = $this->versionManager->startMigration($version, $action, $params, $settag);

        if ($this->versionManager->getLastException()) {
            $this->browserOutput->outException($this->versionManager->getLastException());
        }

        return array_merge([
            'success' => $success,
            'restart' => $this->versionManager->needRestart(),
            'params'  => $this->versionManager->getRestartParams(),
            'meta'    => $this->versionManager->getVersionByName($version)
        ], $this->browserOutput->getLog());
    }
}
