<?php

namespace Sprint\Migration\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Request;
use Sprint\Migration\ConfigManager;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Locale;
use Sprint\Migration\Output;
use Sprint\Migration\Output\BrowserOutput;
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

        $this->output = new BrowserOutput();

        $this->logger = Output::getInstance();
        $this->logger
            ->addOutput($this->output)
            ->addLogger($versionConfig->getLogger());

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
        return [
            'versions' => $this->versionManager->getVersions($filter)
        ];
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */

    public function markVersionAction(string $version, string $status): array
    {
        $success = $this->versionManager->markMigration($version, $status);

        if ($success) {
            $this->output->outInfo(
                Locale::getMessage('MARK_OK', ['#VERSION#' => $version, '#STATUS#' => $status])
            );
        } else {
            $this->output->outWarning(
                Locale::getMessage('MARK_ERROR1', ['#VERSION#' => $version])
            );
        }

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
        $success = $this->versionManager->deleteMigration($version);

        if ($success) {
            $this->output->outInfo(
                Locale::getMessage('DELETE_OK', ['#VERSION#' => $version])
            );
        } else {
            $this->output->outWarning(
                Locale::getMessage('DELETE_ERROR1', ['#VERSION#' => $version])
            );
        }

        return $this->output->getLog();
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function moveVersionAction(string $version, string $toConfig): array
    {
        $success = $this->versionManager->moveMigration($version, $toConfig);

        if ($success) {
            $this->output->outInfo(
                Locale::getMessage('TRANSFER_OK', ['#VERSION#' => $version])
            );
        } else {
            $this->output->outWarning(
                Locale::getMessage('TRANSFER_ERROR1', ['#VERSION#' => $version])
            );
        }

        return $this->output->getLog();
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function setVersionTagAction(string $version, string $settag): array
    {
        $success = $this->versionManager->setMigrationTag($version, $settag);

        if ($success) {
            $this->output->outInfo(
                Locale::getMessage('SETTAG_OK', ['#VERSION#' => $version])
            );
        } else {
            $this->output->outWarning(
                Locale::getMessage('SETTAG_ERROR1', ['#VERSION#' => $version])
            );
        }

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
        foreach ($versions as $version) {
            $success = $this->versionManager->setMigrationTag($version, $settag);
            if ($success) {
                $this->output->outInfo(
                    Locale::getMessage('SETTAG_OK', ['#VERSION#' => $version])
                );
            } else {
                $this->output->outWarning(
                    Locale::getMessage('SETTAG_ERROR1', ['#VERSION#' => $version])
                );
            }
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

        $success = $this->versionManager->startMigration($version, $action, $params, $settag);

        if ($this->versionManager->getLastException()) {
            $this->logger->outException($this->versionManager->getLastException());
        }

        return array_merge([
            'success' => $success,
            'restart' => $this->versionManager->needRestart(),
            'params'  => $this->versionManager->getRestartParams(),
            'meta'    => $this->versionManager->getVersionByName($version)
        ], $this->output->getLog());
    }
}
