<?php

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;
use Bitrix\Main\Application;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$existsEvents = (
($request->getPost('step_code') == "migration_mark")
);

if ($existsEvents && check_bitrix_sessid('send_sessid')) {
    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $version = !empty($request->getPost('version')) ? $request->getPost('version') : '';
    $status = !empty($request->getPost('status')) ? $request->getPost('status') : '';

    $markresult = $versionManager->markMigration($version, $status);
    Sprint\Migration\Out::outMessages($markresult);

    ?>
    <script>
        migrationMigrationRefresh(function () {
            migrationScrollList();
            migrationEnableButtons(1);
        });
    </script><?
}
