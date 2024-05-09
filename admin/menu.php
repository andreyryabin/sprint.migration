<?php
global $APPLICATION;

use Bitrix\Main\Loader;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;

if ($APPLICATION->GetGroupRight('sprint.migration') == 'D') {
    return false;
}

if (!Loader::includeModule('sprint.migration')) {
    return false;
}

try {
    $versionConfig = new Sprint\Migration\VersionConfig();
    $configList = $versionConfig->getList();

    $items = [];
    foreach ($configList as $item) {
        $items[] = [
            'text' => $item['title'],
            'url'  => 'sprint_migrations.php?' . http_build_query([
                    'config' => $item['name'],
                    'lang'   => LANGUAGE_ID,
                ]),
        ];
    }

    if (Module::isDbOptionChecked('show_schemas')) {
        $schemas = [];
        foreach ($configList as $item) {
            $schemas[] = [
                'text' => $item['schema_title'],
                'url'  => 'sprint_migrations.php?' . http_build_query([
                        'schema' => $item['name'],
                        'lang'   => LANGUAGE_ID,
                    ]),
            ];
        }

        $items[] = [
            'items_id' => 'sp-menu-schema',
            'text'     => Locale::getMessage('MENU_SCHEMAS'),
            'items'    => $schemas,
        ];
    }

    if (Module::isDbOptionChecked('show_support')) {
        $items[] = [
            'text' => Locale::getMessage('MENU_SUPPORT'),
            'url'  => 'sprint_migrations.php?' . http_build_query([
                    'showpage' => 'support',
                    'lang'    => LANGUAGE_ID,
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
        'items'       => $items,
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
