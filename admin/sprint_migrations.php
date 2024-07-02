<?php

use Bitrix\Main\Loader;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;

ini_set('zend.exception_ignore_args', 0);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

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

    if (isset($_GET['showpage'])) {
        require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
        $showpage = preg_replace("/[^a-z0-9_]/", "", $_GET['showpage']);
        if ($showpage && file_exists(__DIR__ . '/pages/' . $showpage . '.php')) {
            include __DIR__ . '/pages/' . $showpage . '.php';
        }
    } else {
        include __DIR__ . '/includes/interface.php';
    }

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
} catch (Throwable $exception) {
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

    $sperrors = [];
    $sperrors[] = sprintf(
        "[%s] %s (%s) in %s:%d \n",
        get_class($exception),
        $exception->getMessage(),
        $exception->getCode(),
        $exception->getFile(),
        $exception->getLine()
    );

    include __DIR__ . '/includes/errors.php';
    include __DIR__ . '/includes/help.php';
    include __DIR__ . '/assets/style.php';

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
}
