<?php

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$existsEvents = (
($_POST["step_code"] == "migration_transfer")
);

if ($existsEvents && check_bitrix_sessid('send_sessid')) {

    $version = !empty($_POST['version']) ? $_POST['version'] : '';
    $transferTo = !empty($_POST['transfer_to']) ? $_POST['transfer_to'] : '';

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
        migrationListRefresh(function () {
            migrationListScroll();
            migrationEnableButtons(1);
        });
    </script><?php
}
