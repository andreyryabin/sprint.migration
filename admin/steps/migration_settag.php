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
    foreach ($settagresult as $val) {
        if ($val['success']) {
            Sprint\Migration\Out::outSuccess($val['message']);
        } else {
            Sprint\Migration\Out::outError($val['message']);
        }
    }
    ?>
    <script>
        migrationMigrationRefresh(function () {
            migrationScrollList();
            migrationEnableButtons(1);
        });
    </script><?
}
