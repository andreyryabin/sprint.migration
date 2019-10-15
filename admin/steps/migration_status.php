<?php

use Sprint\Migration\Locale;
use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if ($_POST["step_code"] == "migration_status" && check_bitrix_sessid('send_sessid')) {

    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $search = !empty($_POST['search']) ? trim($_POST['search']) : '';
    $search = Locale::convertToUtf8IfNeed($search);

    $versions = $versionManager->getVersions([
        'status' => '',
        'search' => $search,
    ]);

    $status = [
        'new' => 0,
        'installed' => 0,
        'unknown' => 0,
    ];

    foreach ($versions as $aItem) {
        $key = $aItem['status'];
        $status[$key]++;
    }


    ?>
    <table class="sp-status">
        <? foreach ($status as $code => $cnt): $ucode = strtoupper($code); ?>
            <tr>
                <td class="sp-status-l">
                <span class="sp-item-<?= $code ?>">
                    <?= Locale::getMessage($ucode) ?>
                </span>
                    <?= Locale::getMessage('DESC_' . $ucode) ?>
                </td>
                <td class="sp-status-r">
                    <?= $cnt ?>
                </td>
            </tr>
        <? endforeach ?>
    </table>
    <?
}