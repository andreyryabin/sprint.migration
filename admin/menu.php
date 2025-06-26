<?php
global $APPLICATION;

use Bitrix\Main\Loader;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Locale;
use Sprint\Migration\ConfigManager;

if ($APPLICATION->GetGroupRight('sprint.migration') == 'D') {
    return false;
}

if (!Loader::includeModule('sprint.migration')) {
    return false;
}

try {
    $menuItems = [];
    foreach (ConfigManager::getInstance()->getList() as $configItem) {
        $menuItems[] = [
            'text' => $configItem->getTitle(),
            'url'  => 'sprint_migrations.php?' . http_build_query([
                    'config' => $configItem->getName(),
                    'lang'   => LANGUAGE_ID,
                ]),
        ];
    }

    $aMenu = [
        'parent_menu' => 'global_menu_settings',
        'section'     => 'Sprint',
        'sort'        => 50,
        'text'        => Locale::getMessage('MENU_SPRINT'),
        'icon'        => 'sys_menu_icon',
        'page_icon'   => 'sys_page_icon',
        'items_id'    => 'sprint_migrations',
        'items'       => $menuItems,
    ];

    return $aMenu;
} catch (Throwable $e) {
    $aMenu = [
        'parent_menu' => 'global_menu_settings',
        'section'     => 'Sprint',
        'sort'        => 50,
        'text'        => Locale::getMessage('MENU_SPRINT'),
        'icon'        => 'sys_menu_icon',
        'page_icon'   => 'sys_page_icon',
        'items_id'    => 'sprint_migrations',
        'url'         => 'sprint_migrations.php?' . http_build_query([
                'config' => VersionEnum::CONFIG_DEFAULT,
                'lang'   => LANGUAGE_ID,
            ]),
    ];

    return $aMenu;
}
