<?php

/** @noinspection PhpIncludeInspection */
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

\CModule::IncludeModule("sprint.migration");

/** @global $APPLICATION \CMain */

global $APPLICATION;
$APPLICATION->SetTitle(GetMessage('SPRINT_MIGRATION_TITLE'));

if ($APPLICATION->GetGroupRight("sprint.migration") == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    CUtil::JSPostUnescape();
}

$versionManager = new Sprint\Migration\VersionManager();

if ($versionManager->getConfigVal('show_admin_interface')) {
    include __DIR__ . '/steps/migration_config.php';
    include __DIR__ . '/steps/migration_execute.php';
    include __DIR__ . '/steps/migration_list.php';
    include __DIR__ . '/steps/migration_status.php';
    include __DIR__ . '/steps/migration_mark.php';
    include __DIR__ . '/steps/migration_create.php';
}

/** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
\CUtil::InitJSCore(Array("jquery"));

if ($versionManager->getConfigVal('show_admin_interface')) {
    include __DIR__ . '/includes/interface.php';
}

include __DIR__ . '/includes/help.php';
include __DIR__ . '/assets/assets.php';

/** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");