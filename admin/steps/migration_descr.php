<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_descr" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $version = isset($_POST['version']) ? $_POST['version'] : 0;
    $descr = $manager->getDescription($version);
    $descr = !empty($descr) ? $descr : GetMessage('SPRINT_MIGRATION_NO_DESCRSPRINT_MIGRATIONS');

    ?>
    <div class="c-migration-descr"><?= $descr ?></div>
    <?

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}