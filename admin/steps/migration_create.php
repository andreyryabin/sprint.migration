<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_create" && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $name = !empty($_POST['builder_name']) ? trim($_POST['builder_name']) : '';

    $builder = $versionManager->createVersionBuilder($name);

    $builder->bind($_POST);

    $builder->build();

    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}