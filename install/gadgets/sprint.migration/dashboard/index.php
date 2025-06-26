<?php
/**
 * @var $arGadgetParams array
 */

use Bitrix\Main\Loader;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionManager;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\ConfigManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
global $APPLICATION;
try {

    if (!Loader::includeModule('sprint.migration')) {
        Throw new Exception('need to install module sprint.migration');
    }

    if ($APPLICATION->GetGroupRight('sprint.migration') == 'D') {
        Throw new Exception(Locale::getMessage("ACCESS_DENIED"));
    }

    Module::checkHealth();

    $arGadgetParams['SELECT_CONFIGS'] = is_array($arGadgetParams['SELECT_CONFIGS']) ? $arGadgetParams['SELECT_CONFIGS'] : [];

    $results = [];

    foreach (ConfigManager::getInstance()->getList() as $configItem) {
        if (!empty($arGadgetParams['SELECT_CONFIGS'])) {
            if (!in_array($configItem->getName(), $arGadgetParams['SELECT_CONFIGS'])) {
                continue;
            }
        }

        $versionManager = new VersionManager($configItem);
        $hasNewVersions = count($versionManager->getVersions([
            'status' => VersionEnum::STATUS_NEW,
        ]));

        $results[] = [
            'title' => $configItem->getTitle(),
            'text' => ($hasNewVersions) ? Locale::getMessage('GD_MIGRATIONS_RED') : Locale::getMessage('GD_MIGRATIONS_GREEN'),
            'state' => ($hasNewVersions) ? 'red' : 'green',
            'buttons' => [
                [
                    'text' => Locale::getMessage('GD_SHOW'),
                    'title' => Locale::getMessage('GD_SHOW_MIGRATIONS'),
                    'url' => '/bitrix/admin/sprint_migrations.php?' . http_build_query([
                            'config' => $configItem->getName(),
                            'lang' => LANGUAGE_ID,
                        ]),
                ],
            ],
        ];
    }

    include __DIR__ . '/includes/style.php';
    include __DIR__ . '/includes/interface.php';

} catch (Throwable $e) {
    include __DIR__ . '/includes/style.php';
    include __DIR__ . '/includes/errors.php';
}
