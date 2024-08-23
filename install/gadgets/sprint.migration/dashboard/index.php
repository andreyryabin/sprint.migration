<?php
/**
 * @var $arGadgetParams array
 */

use Bitrix\Main\Loader;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\SchemaManager;
use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;
use Sprint\Migration\Enum\VersionEnum;

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
    $arGadgetParams['CHECK_SCHEMAS'] = is_array($arGadgetParams['CHECK_SCHEMAS']) ? $arGadgetParams['CHECK_SCHEMAS'] : [];

    $results = [];

    $configs = (new VersionConfig())->getList();
    foreach ($configs as $config) {

        if (!empty($arGadgetParams['SELECT_CONFIGS'])) {
            if (!in_array($config['name'], $arGadgetParams['SELECT_CONFIGS'])) {
                continue;
            }
        }

        $versionManager = new VersionManager(
            new VersionConfig($config['name'])
        );
        $hasNewVersions = count($versionManager->getVersions([
            'status' => VersionEnum::STATUS_NEW,
        ]));

        $results[] = [
            'title' => $config['title'],
            'text' => ($hasNewVersions) ? Locale::getMessage('GD_MIGRATIONS_RED') : Locale::getMessage('GD_MIGRATIONS_GREEN'),
            'state' => ($hasNewVersions) ? 'red' : 'green',
            'buttons' => [
                [
                    'text' => Locale::getMessage('GD_SHOW'),
                    'title' => Locale::getMessage('GD_SHOW_MIGRATIONS'),
                    'url' => '/bitrix/admin/sprint_migrations.php?' . http_build_query([
                            'config' => $config['name'],
                            'lang' => LANGUAGE_ID,
                        ]),
                ],
            ],
        ];

        if (!empty($arGadgetParams['CHECK_SCHEMAS'])) {

            $schemaManager = new SchemaManager(
                new VersionConfig($config['name'])
            );

            $modifiedCnt = 0;
            $enabledSchemas = $schemaManager->getEnabledSchemas();
            foreach ($enabledSchemas as $schema) {
                if (!in_array($schema->getName(), $arGadgetParams['CHECK_SCHEMAS'])) {
                    continue;
                }

                if ($schema->isModified()) {
                    $modifiedCnt++;
                }
            }

            $results[] = [
                'title' => $config['schema_title'],
                'text' => ($modifiedCnt) ? Locale::getMessage('GD_SCHEMA_RED') : Locale::getMessage('GD_SCHEMA_GREEN'),
                'state' => ($modifiedCnt) ? 'red' : 'green',
                'buttons' => [
                    [
                        'text' => Locale::getMessage('GD_SHOW'),
                        'title' => Locale::getMessage('GD_SHOW_SCHEMAS'),
                        'url' => '/bitrix/admin/sprint_migrations.php?' . http_build_query([
                                'schema' => $config['name'],
                                'lang' => LANGUAGE_ID,
                            ]),
                    ],
                ],
            ];
        }
    }

    include __DIR__ . '/includes/style.php';
    include __DIR__ . '/includes/interface.php';

} catch (Throwable $e) {
    include __DIR__ . '/includes/style.php';
    include __DIR__ . '/includes/errors.php';
}
