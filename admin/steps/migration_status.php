<?php

use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Locale;
use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if ($_POST["step_code"] == "migration_view_status" && check_bitrix_sessid('send_sessid')) {
    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $search = !empty($_POST['search']) ? trim($_POST['search']) : '';
    $search = Locale::convertToUtf8IfNeed($search);

    $versions = $versionManager->getVersions(
        [
            'search' => $search,
        ]
    );

    $status = [
        VersionEnum::STATUS_NEW       => 0,
        VersionEnum::STATUS_INSTALLED => 0,
        VersionEnum::STATUS_UNKNOWN   => 0,
    ];

    foreach ($versions as $item) {
        $key = $item['status'];
        $status[$key]++;
    }

    ?>
    <table class="sp-summary">
        <?php foreach ($status as $code => $cnt) {
            $ucode = strtoupper($code); ?>
            <tr>
                <td class="sp-summary-l">
                <span class="sp-item-<?= $code ?>">
                    <?= Locale::getMessage($ucode) ?>
                </span>
                    <?= Locale::getMessage('DESC_' . $ucode) ?>
                </td>
                <td class="sp-summary-r">
                    <?= $cnt ?>
                </td>
            </tr>
        <?php } ?>
    </table>
    <?php
}
