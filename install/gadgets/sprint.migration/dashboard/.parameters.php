<?php

use Bitrix\Main\Loader;
use Sprint\Migration\Locale;
use Sprint\Migration\ConfigManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!Loader::includeModule('sprint.migration')) {
    return false;
}

$arParameters = [
    'USER_PARAMETERS' => [
        'SELECT_CONFIGS' => [
            'NAME' => Locale::getMessage('GD_SELECT_CONFIGS'),
            'TYPE' => 'LIST',
            'SIZE' => 10,
            'VALUES' => ConfigManager::getInstance()->getListAssoc(),
            'MULTIPLE' => 'Y',
            'DEFAULT' => [],
        ],
    ],
];
