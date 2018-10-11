<?php
global $APPLICATION;

if ($APPLICATION->GetGroupRight("sprint.migration") == "D") {
    return false;
}

\CModule::IncludeModule('sprint.migration');

$versionConfig = new Sprint\Migration\VersionConfig();
$configList = $versionConfig->getList();

$items = array();
foreach ($configList as $item) {
    $items[] = array(
        "text" => $item['title'],
        "url" => "sprint_migrations.php?config=" . $item['name'] . "&lang=" . LANGUAGE_ID,
    );
}

$aMenu = array(
    "parent_menu" => "global_menu_settings",
    "section" => "Sprint",
    "sort" => 50,
    "text" => GetMessage('SPRINT_MIGRATION_MENU_SPRINT'),
    "icon" => "sys_menu_icon",
    "page_icon" => "sys_page_icon",
    "items_id" => "sprint_migrations",
    "items" => $items
);

return $aMenu;
