<?php

use Sprint\Migration\ConfigManager;
use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;
use Sprint\Migration\Output;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$existsEvents = (
($_POST["step_code"] == "migration_transfer")
);

if ($existsEvents && check_bitrix_sessid()) {
    $version = !empty($_POST['version']) ? $_POST['version'] : '';
    $transferTo = !empty($_POST['transfer_to']) ? $_POST['transfer_to'] : '';

    $transferConfig = ConfigManager::getInstance()->get($transferTo);
    $logger = Output::getInstance();

    /** @var $versionConfig VersionConfig */
    $vmFrom = new VersionManager($versionConfig);
    $vmTo = new VersionManager($transferConfig);

    $transferresult = $vmFrom->transferMigration(
        $version,
        $vmTo
    );

    $logger->outMessages($transferresult);
    ?>
    <script>
        migrationListRefresh(function () {
            migrationListScroll();
            migrationEnableButtons(1);
        });
    </script><?php
}
