<?php

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;
use Sprint\Migration\Output;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$existsEvents = (
($_POST["step_code"] == "migration_settag")
);

if ($existsEvents && check_bitrix_sessid()) {
    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);
    $logger = Output::getInstance();

    $versionName = !empty($_POST['version']) ? (string)$_POST['version'] : '';
    $versionConfig->tryVersionName($versionName);

    $settag = !empty($_POST['settag']) ? htmlspecialcharsbx(trim($_POST['settag'])) : '';

    $settagresult = $versionManager->setMigrationTag($versionName, $settag);
    $logger->outMessages($settagresult);
    ?>
    <script>
        migrationListRefresh(function () {
            migrationListScroll();
            migrationEnableButtons(1);
        });
    </script><?php
}
