<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_status" && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");
    \Sprint\Migration\Module::setDbOption('admin_versions_view', 'status');
    $status = $versionManager->getStatus();
    ?>
    <table style="border-collapse: collapse;width: 100%">
    <?foreach ($status as $type => $cnt): $uptype = strtoupper($type);?>
        <tr>
            <td style="width:50%;text-align: left;padding: 5px;">
                <span class="c-migration-item-<?= $type ?>">
                    <?= GetMessage('SPRINT_MIGRATION_' . $uptype) ?>
                </span>
                <?= GetMessage('SPRINT_MIGRATION_DESC_' . $uptype) ?>
            </td>
            <td style="width:50%;text-align: left;padding: 5px;">
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