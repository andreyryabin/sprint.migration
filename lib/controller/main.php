<?php

namespace Sprint\Migration\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Request;
use Sprint\Migration\ConfigManager;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Output;
use Sprint\Migration\Output\HtmlOutput;
use Sprint\Migration\VersionManager;
use Throwable;

/**
 * @noinspection PhpUnused
 * controller: sprint:migration.controller.main
 */
class Main extends Controller
{
    protected VersionManager $versionManager;
    protected Output $logger;


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

        $this->logger = Output::getInstance();
        $this->logger->addOutput(new HtmlOutput());

        $this->versionManager = new VersionManager($versionConfig);
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
    public function refreshAction(array $filter = []): array
    {
        return ['versions' => $this->versionManager->getVersions($filter)];
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */

    public function versionMarkAction(string $version, string $status): array
    {
        $res = $this->versionManager->markMigration($version, $status);

        $this->logger->outMessages($res);

        return ['messages' => $this->logger->getMessages()];
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function versionDeleteAction(string $version): array
    {
        $res = $this->versionManager->deleteMigration($version);

        $this->logger->outMessages($res);

        return ['messages' => $this->logger->getMessages()];
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function versionTransferAction(string $version, string $toConfig): array
    {
        $res = $this->versionManager->transferMigration($version, $toConfig);

        $this->logger->outMessages($res);

        return ['messages' => $this->logger->getMessages()];
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function versionSetTagAction(string $version, string $settag): array
    {
        $res = $this->versionManager->setMigrationTag($version, $settag);

        $this->logger->outMessages($res);

        return ['messages' => $this->logger->getMessages()];
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     * @throws Throwable
     */
    public function versionStartAction(string $version, string $action, array $params = [], string $settag = ''): array
    {
        $action = ($action === VersionEnum::ACTION_DOWN) ? VersionEnum::ACTION_DOWN : VersionEnum::ACTION_UP;

        $success = $this->versionManager->startMigration($version, $action, $params, $settag);

        if ($this->versionManager->getLastException()) {
            $this->logger->outException($this->versionManager->getLastException());
        }

        return [
            'success'  => $success,
            'restart'  => $this->versionManager->needRestart(),
            'params'   => $this->versionManager->getRestartParams(),
            'messages' => $this->logger->getMessages(),
        ];
    }
}
