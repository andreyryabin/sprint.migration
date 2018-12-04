<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$hasSteps = (
($_POST["step_code"] == "schema_export")
);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $hasSteps && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");


    $schemaManager = new \Sprint\Migration\SchemaManager();
    $schemaManager->export();


    ?><script>schemaRefresh();</script><?

    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}