<?php

namespace Sprint\Migration\Controller;

use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Engine\Controller;

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
    public function refreshAction()
    {

    }
}
