<?php
global $APPLICATION;

if ($APPLICATION->GetGroupRight('sprint.migration') == 'D') {
    return false;
}

if (!\CModule::IncludeModule('sprint.migration')) {
    return false;
}

$versionConfig = new Sprint\Migration\VersionConfig();
$configList = $versionConfig->getList();

$schemas = array();
foreach ($configList as $item) {
    $schemas[] = array(
        'text' => GetMessage('SPRINT_MIGRATION_MENU_SCHEMA') . ' (' . $item['name'] . ')',
        'url' => 'sprint_migrations.php?' . http_build_query(array(
                'schema' => $item['name'],
                'lang' => LANGUAGE_ID,
            ))
    );
}

$items = array();
foreach ($configList as $item) {
    $items[] = array(
        'text' => $item['title'],
        'url' => 'sprint_migrations.php?' . http_build_query(array(
                'config' => $item['name'],
                'lang' => LANGUAGE_ID,
            ))
    );
}

$items[] = array(
    'items_id' => 'sp-menu-schema',
    'text' => GetMessage('SPRINT_MIGRATION_MENU_SCHEMAS'),
    'items' => $schemas,
);

$aMenu = array(
    'parent_menu' => 'global_menu_settings',
    'section' => 'Sprint',
    'sort' => 50,
    'text' => GetMessage('SPRINT_MIGRATION_MENU_SPRINT'),
    'icon' => 'sys_menu_icon',
    'page_icon' => 'sys_page_icon',
    'items_id' => 'sprint_migrations',
    'items' => $items

);

return $aMenu;