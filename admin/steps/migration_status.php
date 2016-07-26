<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_status" && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    \Sprint\Migration\Module::setDbOption('admin_versions_view', 'status');

    $status = $versionManager->getStatus();
    $titles = array(
        'is_new' => GetMessage('SPRINT_MIGRATION_IS_NEW'),
        'is_installed' => GetMessage('SPRINT_MIGRATION_IS_INSTALLED'),
        'is_unknown' => GetMessage('SPRINT_MIGRATION_IS_UNKNOWN'),
    );
    ?>
    <table style="border-collapse: collapse;width: 100%">
    <?foreach ($status as $type => $cnt): ?>
        <tr>
            <td style="width:50%;text-align: right;padding: 5px 5px;">
                <span class="c-migration-item-<?= $type ?>"><?=$titles[$type]?>:</span>
            </td>
            <td style="width:50%;text-align: left;padding: 5px 5px;">
                <?= $cnt ?>
            </td>
        </tr>
    <?endforeach ?>
    </table>
    <?
    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}