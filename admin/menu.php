<?php
global $APPLICATION;

use Bitrix\Main\Loader;

if ($APPLICATION->GetGroupRight('sprint.migration') == 'D') {
    return false;
}

if (!Loader::includeModule('sprint.migration')) {
    return false;
}

$versionConfig = new Sprint\Migration\VersionConfig();
$configList = $versionConfig->getList();

$schemas = [];
foreach ($configList as $item) {
    $schemas[] = [
        'text' => GetMessage('SPRINT_MIGRATION_MENU_SCHEMA') . ' (' . $item['name'] . ')',
        'url' => 'sprint_migrations.php?' . http_build_query([
                'schema' => $item['name'],
                'lang' => LANGUAGE_ID,
            ]),
    ];
}

$items = [];
foreach ($configList as $item) {
    $items[] = [
        'text' => $item['title'],
        'url' => 'sprint_migrations.php?' . http_build_query([
                'config' => $item['name'],
                'lang' => LANGUAGE_ID,
            ]),
    ];
}

$items[] = [
    'items_id' => 'sp-menu-schema',
    'text' => GetMessage('SPRINT_MIGRATION_MENU_SCHEMAS'),
    'items' => $schemas,
];

$aMenu = [
    'parent_menu' => 'global_menu_settings',
    'section' => 'Sprint',
    'sort' => 50,
    'text' => GetMessage('SPRINT_MIGRATION_MENU_SPRINT'),
    'icon' => 'sys_menu_icon',
    'page_icon' => 'sys_page_icon',
    'items_id' => 'sprint_migrations',
    'items' => $items,

];

return $aMenu;