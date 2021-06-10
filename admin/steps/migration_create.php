<?php

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$stepCode = !empty($_POST["step_code"]) ? htmlspecialchars($_POST["step_code"]) : '';
$hasSteps = (
    ($stepCode == 'migration_create')
    || ($stepCode == 'migration_reset')

);

if ($hasSteps && check_bitrix_sessid('send_sessid')) {
    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $builderName = !empty($_POST['builder_name']) ? trim($_POST['builder_name']) : '';

    $builder = $versionManager->createBuilder($builderName, $_POST);

    if ($builder && $stepCode == 'migration_create') {
        $builder->buildExecute();
        $builder->buildAfter();

        $builder->renderHtml();

        if ($builder->isRestart()) {
            $json = json_encode($builder->getRestartParams());
            ?>
            <script>migrationBuilder(<?=$json?>);</script><?php
        } elseif ($builder->isRebuild()) {
            ?>
            <script>migrationEnableButtons(1);</script><?php
        } else {
            ?>
            <script>
                migrationMigrationRefresh(function () {
                    migrationScrollList();
                    migrationEnableButtons(1);
                });
            </script><?php
        }
    } elseif ($builder && $stepCode == 'migration_reset') {
        $builder->renderHtml();
        ?>
        <script>
            migrationEnableButtons(1);
        </script><?php
    }
}
