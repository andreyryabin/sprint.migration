<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_status" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    \Sprint\Migration\Module::setDbOption('admin_versions_view', 'status');

    $status = $versionManager->getStatus();
    $titles = array(
        'is_new' => GetMessage('SPRINT_MIGRATION_IS_NEW'),
        'is_installed' => GetMessage('SPRINT_MIGRATION_IS_INSTALLED'),
        'is_unknown' => GetMessage('SPRINT_MIGRATION_IS_UNKNOWN'),
    );
    ?>
    <?foreach ($status as $type => $cnt): ?>
        <div class="c-migration-block">
            <span class="c-migration-item-<?= $type ?>"><?=$titles[$type]?></span>: <span><?= $cnt ?></span>
        </div>
    <?endforeach ?>
    <?
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}