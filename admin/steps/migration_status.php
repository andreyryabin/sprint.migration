<?php

use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Locale;
use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;
use Bitrix\Main\Application;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
$request = Application::getInstance()->getContext()->getRequest();


if ($request->getPost('step_code') == "migration_view_status" && check_bitrix_sessid('send_sessid')) {

    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $search = !empty($request->getPost('search')) ? trim($request->getPost('search')) : '';
    $search = Locale::convertToUtf8IfNeed($search);

    $versions = $versionManager->getVersions([
        'status' => '',
        'search' => $search,
    ]);

    $status = [
        VersionEnum::STATUS_NEW => 0,
        VersionEnum::STATUS_INSTALLED => 0,
        VersionEnum::STATUS_UNKNOWN => 0,
    ];

    foreach ($versions as $item) {
        $key = $item['status'];
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
