<?php

namespace Sprint\Migration\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;
use Sprint\Migration\ConfigManager;
use Sprint\Migration\VersionManager;

/**
 * @noinspection PhpUnused
 * controller: sprint:migration.controller.main
 */
class Main extends Controller
{
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
     * controller: sprint:migration.controller.main.refresh
     */
    public function refreshAction(string $config, array $filter)
    {
        $versionConfig = ConfigManager::getInstance()->get($config);

        $versionManager = new VersionManager($versionConfig);

        $versions = $versionManager->getVersions($filter);

        return ['versions' => $versions];

    }
}
