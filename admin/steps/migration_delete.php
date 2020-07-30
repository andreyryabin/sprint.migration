<?php

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;
use Bitrix\Main\Application;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
$request = Application::getInstance()->getContext()->getRequest();


$existsEvents = (
($request->getPost('step_code') == "migration_delete")
);

if ($existsEvents && check_bitrix_sessid('send_sessid')) {

    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $version = !empty($request->getPost('version')) ? $request->getPost('version') : '';

    $deleteresult = $versionManager->deleteMigration($version);
    Sprint\Migration\Out::outMessages($deleteresult);

    ?>
    <script>
        migrationMigrationRefresh(function () {
            migrationScrollList();
            migrationEnableButtons(1);
        });
    </script><?
}
