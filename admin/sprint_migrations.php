<?php

/** @noinspection PhpIncludeInspection */
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

/** @global $APPLICATION \CMain */
global $APPLICATION;

try {
    if (!\CModule::IncludeModule('sprint.migration')) {
        Throw new \Exception('need to install module sprint.migration');
    }

    if (!$APPLICATION->GetGroupRight("sprint.migration") >= "R") {
        Throw new \Exception(GetMessage("ACCESS_DENIED"));
    }

    \Sprint\Migration\Module::checkHealth();

} catch (\Exception $e) {
    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

    $sperrors = array();
    $sperrors[] = $e->getMessage();

    include __DIR__ . '/includes/errors.php';
    include __DIR__ . '/includes/help.php';
    include __DIR__ . '/assets/style.php';

    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
}


$APPLICATION->SetTitle(GetMessage('SPRINT_MIGRATION_TITLE'));

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
    if (isset($_REQUEST['schema'])) {
        include __DIR__ . '/steps/schema_list.php';
        include __DIR__ . '/steps/schema_export.php';
        include __DIR__ . '/steps/schema_import.php';
    } else {
        include __DIR__ . '/steps/migration_execute.php';
        include __DIR__ . '/steps/migration_list.php';
        include __DIR__ . '/steps/migration_status.php';
        include __DIR__ . '/steps/migration_create.php';
    }
}

/** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
\CUtil::InitJSCore(Array("jquery"));

if ($versionConfig->getVal('show_admin_interface')) {
    if (isset($_REQUEST['schema'])) {
        include __DIR__ . '/includes/schema.php';
        include __DIR__ . '/assets/schema.php';
    } else {
        include __DIR__ . '/includes/version.php';
        include __DIR__ . '/assets/version.php';
    }
}

$sperrors = array();
if (!$versionConfig->getVal('show_admin_interface')) {
    $sperrors[] = GetMessage('SPRINT_MIGRATION_ADMIN_INTERFACE_HIDDEN');
}

include __DIR__ . '/includes/errors.php';
include __DIR__ . '/includes/help.php';
include __DIR__ . '/assets/style.php';


/** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");