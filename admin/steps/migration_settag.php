<?php

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;
use Bitrix\Main\Application;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
$request = Application::getInstance()->getContext()->getRequest();


$existsEvents = (
($request->getPost('step_code') == "migration_settag")
);

if ($existsEvents && check_bitrix_sessid('send_sessid')) {
    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $version = !empty($request->getPost('version')) ? $request->getPost('version') : '';
    $settag = !empty($request->getPost('settag')) ? $request->getPost('settag') : '';

    $settagresult = $versionManager->setMigrationTag($version, $settag);
    Sprint\Migration\Out::outMessages($settagresult);
    ?>
    <script>
        migrationMigrationRefresh(function () {
            migrationScrollList();
            migrationEnableButtons(1);
        });
    </script><?
}
