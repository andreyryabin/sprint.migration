<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_create" && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $description = isset($_POST['description']) ? $_POST['description'] : 0;
    $prefix = isset($_POST['prefix']) ? $_POST['prefix'] : 'Version';

    $meta = $versionManager->createVersionFile($description, $prefix);
    if ($meta && $meta['class']) {
        Sprint\Migration\Out::outSuccess('Миграция %s создана', $meta['version']);
    } else {
        Sprint\Migration\Out::outError('Ошибка создания миграции');
    }

    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}