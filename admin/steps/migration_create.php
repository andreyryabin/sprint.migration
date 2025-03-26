<?php

use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;
use Sprint\Migration\Exceptions\BuilderException;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$stepCode = htmlspecialchars($_POST['step_code']??'');
$hasSteps = (($stepCode == 'migration_create') || ($stepCode == 'migration_reset'));

if ($hasSteps && check_bitrix_sessid()) {
    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $builderName = !empty($_POST['builder_name']) ? trim($_POST['builder_name']) : '';

    try {
        $builder = $versionManager->createBuilder($builderName, $_POST);
    } catch (BuilderException $e) {
        return;
    }

    if ($stepCode == 'migration_create') {
        $builder->buildExecute();
        $builder->buildAfter();
        $builder->renderHtml();

        if ($builder->isRestart()) {
            ?>
            <script>migrationBuilderRestart();</script><?php
        } elseif ($builder->isRebuild()) {
            ?>
            <script>migrationEnableButtons(1);</script><?php
        } else {
            ?>
            <script>
                migrationListRefresh(function () {
                    migrationListScroll();
                    migrationEnableButtons(1);
                });
            </script><?php
        }
    } elseif ($stepCode == 'migration_reset') {
        $builder->renderHtml();
        ?>
        <script>
            migrationEnableButtons(1);
        </script><?php
    }
}
