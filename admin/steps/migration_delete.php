<?php

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$existsEvents = (
($_POST["step_code"] == "migration_delete")
);

if ($existsEvents && check_bitrix_sessid('send_sessid')) {

    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $version = !empty($_POST['version']) ? $_POST['version'] : '';

    $deleteresult = $versionManager->deleteMigration($version);
    Sprint\Migration\Out::outMessages($deleteresult);

    ?>
    <script>
        migrationListRefresh(function () {
            migrationListScroll();
            migrationEnableButtons(1);
        });
    </script><?php
}
