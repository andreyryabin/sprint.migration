<?php

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$existsEvents = (
($_POST["step_code"] == "migration_settag")
);

if ($existsEvents && check_bitrix_sessid('send_sessid')) {

    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $version = !empty($_POST['version']) ? $_POST['version'] : '';
    $settag = !empty($_POST['settag']) ? $_POST['settag'] : '';

    $settagresult = $versionManager->setMigrationTag($version, $settag);
    Sprint\Migration\Out::outMessages($settagresult);
    ?>
    <script>
        migrationListRefresh(function () {
            migrationListScroll();
            migrationEnableButtons(1);
        });
    </script><?php
}
