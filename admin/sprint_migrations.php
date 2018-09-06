<?php

/** @noinspection PhpIncludeInspection */
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

/** @global $APPLICATION \CMain */
global $APPLICATION;

try {
    if (!\CModule::IncludeModule('sprint.migration')) {
        Throw new \Exception('need to install module sprint.migration');
    }

    if ($APPLICATION->GetGroupRight("sprint.migration") == "D") {
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
    include __DIR__ . '/assets/assets.php';

    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
}


$APPLICATION->SetTitle(GetMessage('SPRINT_MIGRATION_TITLE'));

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    CUtil::JSPostUnescape();
}

$config = isset($_REQUEST['config']) ? $_REQUEST['config'] : '';
$versionManager = new Sprint\Migration\VersionManager($config);

if ($versionManager->getConfigVal('show_admin_interface')) {
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

$sperrors = array();
if (!$versionManager->getConfigVal('show_admin_interface')) {
    $sperrors[] = GetMessage('SPRINT_MIGRATION_ADMIN_INTERFACE_HIDDEN');
}

include __DIR__ . '/includes/errors.php';
include __DIR__ . '/includes/help.php';
include __DIR__ . '/assets/assets.php';

/** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");