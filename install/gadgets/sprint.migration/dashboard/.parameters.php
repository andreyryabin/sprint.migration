<?php

use Bitrix\Main\Loader;
use Sprint\Migration\Locale;
use Sprint\Migration\VersionConfig;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if (!Loader::includeModule('sprint.migration')) {
    return false;
}

$configs = (new VersionConfig())->getList();

$configValues = [];

foreach ($configs as $config) {
    $configValues[$config['name']] = $config['title'];
}

$arParameters = [
    'USER_PARAMETERS' => [
        'SELECT_CONFIGS' => [
            'NAME' => Locale::getMessage('GD_SELECT_CONFIGS'),
            'TYPE' => 'LIST',
            'SIZE' => 10,
            'VALUES' => $configValues,
            'MULTIPLE' => 'Y',
            'DEFAULT' => [],
        ],
    ],
];
