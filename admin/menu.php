<?
global $APPLICATION;

require_once __DIR__ . '/../classes/Sprint/Migration/Loc.php';

if ($APPLICATION->GetGroupRight("sprint.migration") != "D") {
    $aMenu = array(
        "parent_menu" => "global_menu_services",
        "section" => "Sprint",
        "sort" => 50,
        "text" => Sprint\Migration\Loc::getMessage('SPRINT_MIGRATION_MENU_SPRINT'),
        "icon" => "sys_menu_icon",
        "page_icon" => "sys_page_icon",
        "items_id" => "sprint_migrations",
        "items" => array(
            array(
                "text" => Sprint\Migration\Loc::getMessage('SPRINT_MIGRATION_MENU_MIGRATIONS'),
                "url" => "sprint_migrations.php?lang=" . LANGUAGE_ID,
            ),

        )
    );

    return $aMenu;
}

return false;
