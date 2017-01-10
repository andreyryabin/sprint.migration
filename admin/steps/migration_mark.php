<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_mark" && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $version = isset($_POST['version']) ? $_POST['version'] : 0;
    $status = !empty($_POST['status']) ? $_POST['status'] : 0;

    $markresult = $versionManager->markMigration($version, $status);
    foreach ($markresult as $val){
        if ($val['success']){
            Sprint\Migration\Out::outSuccess($val['message']);
        } else {
            Sprint\Migration\Out::outError($val['message']);
        }
    }

    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}