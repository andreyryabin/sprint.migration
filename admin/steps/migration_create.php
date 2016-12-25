<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_create" && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $description = isset($_POST['description']) ? $_POST['description'] : 0;
    $prefix = isset($_POST['prefix']) ? $_POST['prefix'] : '';

    $meta = $versionManager->createVersionFile($description, $prefix);
    if ($meta && $meta['class']) {
        Sprint\Migration\Out::outSuccess(GetMessage('SPRINT_MIGRATION_CREATED_SUCCESS', array(
            '#VERSION#' => $meta['version']
        )));
    } else {
        Sprint\Migration\Out::outError(GetMessage('SPRINT_MIGRATION_CREATED_ERROR'));
    }

    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}