<?
global $APPLICATION;

include(__DIR__ .'/../loader.php');
include(__DIR__ .'/../locale/ru.php');

if ($APPLICATION->GetGroupRight("sprint.migration") != "D") {
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
}

return false;
