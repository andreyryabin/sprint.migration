<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_create" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $description = isset($_POST['description']) ? $_POST['description'] : 0;
    $versionManager->createVersionFile($description);

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}