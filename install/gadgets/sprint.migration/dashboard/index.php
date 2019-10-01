<?
/**
 * @var $arGadgetParams array
 */

use Bitrix\Main\Loader;
use Sprint\Migration\Module;
use Sprint\Migration\SchemaManager;
use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$results = [];

try {
    if (!Loader::includeModule('sprint.migration')) {
        return false;
    }

    Module::checkHealth();

    $arGadgetParams['SELECT_CONFIGS'] = is_array($arGadgetParams['SELECT_CONFIGS']) ? $arGadgetParams['SELECT_CONFIGS'] : [];
    $arGadgetParams['CHECK_SCHEMAS'] = is_array($arGadgetParams['CHECK_SCHEMAS']) ? $arGadgetParams['CHECK_SCHEMAS'] : [];

    $configs = (new VersionConfig())->getList();
    foreach ($configs as $config) {

        if (!empty($arGadgetParams['SELECT_CONFIGS'])) {
            if (!in_array($config['name'], $arGadgetParams['SELECT_CONFIGS'])) {
                continue;
            }
        }

        $versionManager = new VersionManager(
            $config['name']
        );
        $hasNewVersions = count($versionManager->getVersions([
            'status' => 'new',
        ]));

        $results[] = [
            'title' => $config['title'],
            'text' => ($hasNewVersions) ? GetMessage('MIGRATIONS_RED') : GetMessage('MIGRATIONS_GREEN'),
            'state' => ($hasNewVersions) ? 'red' : 'green',
            'buttons' => [
                [
                    'text' => GetMessage('SHOW'),
                    'title' => GetMessage('SHOW_MIGRATIONS'),
                    'url' => '/bitrix/admin/sprint_migrations.php?' . http_build_query([
                            'config' => $config['name'],
                            'lang' => LANGUAGE_ID,
                        ]),
                ],
            ],
        ];

        if (!empty($arGadgetParams['CHECK_SCHEMAS'])) {

            $schemaManager = new SchemaManager(
                $config['name']
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
                'text' => ($modifiedCnt) ? GetMessage('SCHEMA_RED') : GetMessage('SCHEMA_GREEN'),
                'state' => ($modifiedCnt) ? 'red' : 'green',
                'buttons' => [
                    [
                        'text' => GetMessage('SHOW'),
                        'title' => GetMessage('SHOW_SCHEMAS'),
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

} catch (Exception $e) {
    include __DIR__ . '/includes/style.php';
    include __DIR__ . '/includes/errors.php';
}