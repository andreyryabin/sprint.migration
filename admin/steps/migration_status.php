<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_status" && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $search = !empty($_POST['search']) ? trim($_POST['search']) : '';
    $search = Sprint\Migration\Locale::convertToUtf8IfNeed($search);

    \Sprint\Migration\Module::setDbOption('admin_versions_view', 'status');
    \Sprint\Migration\Module::setDbOption('admin_versions_search', $search);

    $versions = $versionManager->getVersions(array(
        'status' => '',
        'search' => $search,
    ));

    $status = array(
        'new' => 0,
        'installed' => 0,
        'unknown' => 0,
    );

    foreach ($versions as $aItem) {
        $key = $aItem['status'];
        $status[$key]++;
    }


    ?>
    <table class="sp-status">
    <?foreach ($status as $code => $cnt): $ucode = strtoupper($code);?>
        <tr>
            <td class="sp-status-l">
                <span class="sp-item-<?= $code ?>">
                    <?= GetMessage('SPRINT_MIGRATION_' . $ucode) ?>
                </span>
                <?= GetMessage('SPRINT_MIGRATION_DESC_' . $ucode) ?>
            </td>
            <td  class="sp-status-r">
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