<?
global $APPLICATION;

include(__DIR__ .'/../loader.php');
include(__DIR__ .'/../locale/ru.php');

if ($APPLICATION->GetGroupRight("sprint.migration") == "D") {
    return false;
}

$versionManager = new Sprint\Migration\VersionManager();
if (!$versionManager->getConfigVal('show_admin_interface')){
    return false;
}

$aMenu = array(
    "parent_menu" => "global_menu_services",
    "section" => "Sprint",
    "sort" => 50,
    "text" => GetMessage('SPRINT_MIGRATION_MENU_SPRINT'),
    "icon" => "sys_menu_icon",
    "page_icon" => "sys_page_icon",
    "items_id" => "sprint_migrations",
    "items" => array(
        array(
            "text" => GetMessage('SPRINT_MIGRATION_MENU_MIGRATIONS'),
            "url" => "sprint_migrations.php?lang=" . LANGUAGE_ID,
        ),

    )
);

return $aMenu;
