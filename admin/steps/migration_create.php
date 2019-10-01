<?php

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_create" && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $builderName = !empty($_POST['builder_name']) ? trim($_POST['builder_name']) : '';

    $builder = $versionManager->createBuilder($builderName, $_POST);

    if (!$builder) {
        /** @noinspection PhpIncludeInspection */
        require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
        die();
    }

    $builder->buildExecute();
    $builder->buildAfter();

    $builder->renderHtml();

    if ($builder->isRestart()) {
        $json = json_encode($builder->getRestartParams());
        ?>
        <script>migrationBuilder(<?=$json?>);</script><?

    } elseif ($builder->isRebuild()) {
        ?>
        <script>migrationEnableButtons(1);</script><?

    } else {
        ?>
        <script>
            migrationMigrationRefresh(function () {
                migrationScrollList();
                migrationEnableButtons(1);
            });
        </script><?
    }


    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}