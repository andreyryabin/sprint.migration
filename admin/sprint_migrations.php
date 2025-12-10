<?php

use Bitrix\Main\Loader;
use Sprint\Migration\ConfigManager;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

include __DIR__ . '/assets/style.php';

/** @global $APPLICATION CMain */
global $APPLICATION;

try {
    if (!Loader::includeModule('sprint.migration')) {
        throw new Exception('need to install module sprint.migration');
    }

    if ($APPLICATION->GetGroupRight('sprint.migration') == 'D') {
        throw new Exception(Locale::getMessage("ACCESS_DENIED"));
    }

    Module::checkHealth();

    $versionConfig = ConfigManager::getInstance()->get(
        $_REQUEST['config'] ?? VersionEnum::CONFIG_DEFAULT
    );

    $APPLICATION->SetTitle($versionConfig->getTitle());

    if ($versionConfig->getVal('show_admin_interface')) {
        include __DIR__ . '/assets/script.php';
    } else {
        include __DIR__ . '/includes/help.php';
    }
} catch (Throwable $exception) {
    include __DIR__ . '/includes/errors.php';
}

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
