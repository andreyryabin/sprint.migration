<?php

use Sprint\Migration\Locale;
use Sprint\Migration\Out;
use Bitrix\Main\Application;

$request = Application::getInstance()->getContext()->getRequest();
$requestArray = $request->getPostList()->toArray() + $request->getQueryList()->toArray();


$APPLICATION->SetTitle(Locale::getMessage('TITLE'));

if ($request->isPost()) {
    CUtil::JSPostUnescape();
}

if (isset($requestArray['schema'])) {
    $versionConfig = new Sprint\Migration\VersionConfig($requestArray['schema']);
} elseif (isset($requestArray['config'])) {
    $versionConfig = new Sprint\Migration\VersionConfig($requestArray['config']);
} else {
    $versionConfig = new Sprint\Migration\VersionConfig();
}

if ($versionConfig->getVal('show_admin_interface')) {
    if ($request->isPost()) {
        /** @noinspection PhpIncludeInspection */
        require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

        try {
            if (isset($requestArray['schema'])) {
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
        } catch (Exception $e) {
            Out::outError($e->getMessage());
        } catch (Throwable $e) {
            Out::outError($e->getMessage());
        }

        require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
        die();
    }
}

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");
CUtil::InitJSCore(["jquery"]);

if ($versionConfig->getVal('show_admin_interface')) {
    if (isset($requestArray['schema'])) {
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
