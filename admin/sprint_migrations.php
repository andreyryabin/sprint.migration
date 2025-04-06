<?php

use Bitrix\Main\Loader;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\Out;

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

    $versionConfig = new Sprint\Migration\VersionConfig($_REQUEST['config'] ?? '');

    if ($_SERVER["REQUEST_METHOD"] == "POST" && $versionConfig->getVal('show_admin_interface')) {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

        try {
            include __DIR__ . '/steps/migration_execute.php';
            include __DIR__ . '/steps/migration_list.php';
            include __DIR__ . '/steps/migration_status.php';
            include __DIR__ . '/steps/migration_create.php';
            include __DIR__ . '/steps/migration_mark.php';
            include __DIR__ . '/steps/migration_delete.php';
            include __DIR__ . '/steps/migration_settag.php';
            include __DIR__ . '/steps/migration_transfer.php';
        } catch (Throwable $e) {
            Out::outException($e);
        }

        require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
        die();
    }


    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

    $APPLICATION->SetTitle($versionConfig->getCurrent('title'));

    CJSCore::Init(["jquery3"]);

    if ($versionConfig->getVal('show_admin_interface')) {
        include __DIR__ . '/includes/version.php';
        include __DIR__ . '/assets/version.php';
    }

    $sperrors = [];
    if (!$versionConfig->getVal('show_admin_interface')) {
        $sperrors[] = Locale::getMessage('ADMIN_INTERFACE_HIDDEN');
    }

    include __DIR__ . '/includes/errors.php';
    include __DIR__ . '/includes/help.php';
    include __DIR__ . '/assets/style.php';

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
