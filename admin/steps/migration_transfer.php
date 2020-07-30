<?php

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;
use Bitrix\Main\Application;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
$request = Application::getInstance()->getContext()->getRequest();


$existsEvents = (
($request->getPost('step_code') == "migration_transfer")
);

if ($existsEvents && check_bitrix_sessid('send_sessid')) {

    $version = !empty($request->getPost('version')) ? $request->getPost('version') : '';
    $transferTo = !empty($request->getPost('transfer_to')) ? $request->getPost('transfer_to') : '';

    /** @var $versionConfig VersionConfig */
    $vmFrom = new VersionManager($versionConfig);
    $vmTo = new VersionManager($transferTo);

    $transferresult = $vmFrom->transferMigration(
        $version,
        $vmTo
    );

    Sprint\Migration\Out::outMessages($transferresult);
    ?>
    <script>
        migrationMigrationRefresh(function () {
            migrationScrollList();
            migrationEnableButtons(1);
        });
    </script><?
}
