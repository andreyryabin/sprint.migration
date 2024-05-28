<?php

use Sprint\Migration\Locale;
use Sprint\Migration\Out;

global $APPLICATION;
if (isset($_REQUEST['schema'])) {
    $APPLICATION->SetTitle(Locale::getMessage('MENU_SCHEMAS'));
} else {
    $APPLICATION->SetTitle(Locale::getMessage('TITLE'));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    CUtil::JSPostUnescape();
}

if (isset($_REQUEST['schema'])) {
    $versionConfig = new Sprint\Migration\VersionConfig($_REQUEST['schema']);
} elseif (isset($_REQUEST['config'])) {
    $versionConfig = new Sprint\Migration\VersionConfig($_REQUEST['config']);
} else {
    $versionConfig = new Sprint\Migration\VersionConfig();
}

if ($versionConfig->getVal('show_admin_interface')) {
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

        try {
            if (isset($_REQUEST['schema'])) {
                include __DIR__ . '/../steps/schema_list.php';
                include __DIR__ . '/../steps/schema_export.php';
                include __DIR__ . '/../steps/schema_import.php';
            } else {
                include __DIR__ . '/../steps/migration_execute.php';
                include __DIR__ . '/../steps/migration_list.php';
                include __DIR__ . '/../steps/migration_status.php';
                include __DIR__ . '/../steps/migration_create.php';
                include __DIR__ . '/../steps/migration_mark.php';
                include __DIR__ . '/../steps/migration_delete.php';
                include __DIR__ . '/../steps/migration_settag.php';
                include __DIR__ . '/../steps/migration_transfer.php';
            }
        } catch (Throwable $e) {
            Out::outException($e);
        }

        require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
        die();
    }
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
CJSCore::Init(["jquery3"]);

if ($versionConfig->getVal('show_admin_interface')) {
    if (isset($_REQUEST['schema'])) {
        include __DIR__ . '/../includes/schema.php';
        include __DIR__ . '/../assets/schema.php';
    } else {
        include __DIR__ . '/../includes/version.php';
        include __DIR__ . '/../assets/version.php';
    }
}

$sperrors = [];
if (!$versionConfig->getVal('show_admin_interface')) {
    $sperrors[] = Locale::getMessage('ADMIN_INTERFACE_HIDDEN');
}

include __DIR__ . '/../includes/errors.php';
include __DIR__ . '/../includes/help.php';
include __DIR__ . '/../assets/style.php';
