<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();
/** @var $versionManager \Sprint\Migration\VersionManager */

$hasSteps = (
($_POST["step_code"] == "schema_list")
);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $hasSteps && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $schemaManager = new \Sprint\Migration\SchemaManager(
        $versionManager->getVersionConfig()->getName()
    );

    $schemaManager->outDescriptions();

    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}