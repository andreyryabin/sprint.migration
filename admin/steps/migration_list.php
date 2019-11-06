<?php

use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Locale;
use Sprint\Migration\Out;
use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$listView = (
    ($_POST["step_code"] == "migration_view_all") ||
    ($_POST["step_code"] == "migration_view_new") ||
    ($_POST["step_code"] == "migration_view_tag") ||
    ($_POST["step_code"] == "migration_view_installed")
);

if ($listView && check_bitrix_sessid('send_sessid')) {

    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $search = !empty($_POST['search']) ? trim($_POST['search']) : '';
    $search = Sprint\Migration\Locale::convertToUtf8IfNeed($search);

    $webdir = $versionManager->getWebDir();

    if ($_POST["step_code"] == "migration_view_new") {
        $versions = $versionManager->getVersions([
            'status' => VersionEnum::STATUS_NEW,
            'search' => $search,
        ]);
    } elseif ($_POST["step_code"] == "migration_view_installed") {
        $versions = $versionManager->getVersions([
            'status' => VersionEnum::STATUS_INSTALLED,
            'search' => $search,
        ]);
    } elseif ($_POST["step_code"] == "migration_view_tag") {
        $versions = $versionManager->getVersions([
            'tag' => $search,
        ]);
    } else {
        $versions = $versionManager->getVersions([
            'status' => '',
            'search' => $search,
        ]);
    }

    ?>
    <? if (!empty($versions)): ?>
        <table class="sp-list">
            <? foreach ($versions as $aItem): ?>
                <tr>
                    <td class="sp-list-l">
                        <? if ($aItem['status'] == VersionEnum::STATUS_NEW): ?>
                            <input disabled="disabled"
                                   onclick="migrationExecuteStep('migration_execute', {version: '<?= $aItem['version'] ?>', action: '<?= VersionEnum::ACTION_UP ?>'});"
                                   value="<?= Locale::getMessage('UP') ?>" type="button">
                        <? endif ?>
                        <? if ($aItem['status'] == VersionEnum::STATUS_INSTALLED): ?>
                            <input disabled="disabled"
                                   onclick="migrationExecuteStep('migration_execute', {version: '<?= $aItem['version'] ?>', action: '<?= VersionEnum::ACTION_DOWN ?>'});"
                                   value="<?= Locale::getMessage('DOWN') ?>" type="button">
                        <? endif ?>
                    </td>
                    <td class="sp-list-r">
                        <? if ($aItem['status'] != VersionEnum::STATUS_UNKNOWN && $webdir): ?>
                            <? $href = '/bitrix/admin/fileman_file_view.php?' . http_build_query([
                                    'lang' => LANGUAGE_ID,
                                    'site' => SITE_ID,
                                    'path' => $webdir . '/' . $aItem['version'] . '.php',
                                ]) ?>
                            <a class="sp-item-<?= $aItem['status'] ?>" href="<?= $href ?>" target="_blank"
                               title=""><?= $aItem['version'] ?></a>
                        <? else: ?>
                            <span class="sp-item-<?= $aItem['status'] ?>"><?= $aItem['version'] ?></span>
                        <? endif ?>

                        <? if ($aItem['modified']): ?>
                            <span class="sp-modified"
                                  title="<?= Locale::getMessage('MODIFIED_VERSION') ?>"><?= Locale::getMessage('MODIFIED_LABEL') ?></span>
                        <? endif; ?>

                        <? if ($aItem['tag']): ?>
                            <span class="sp-tag" title="<?= Locale::getMessage('TAG') ?>"><?= $aItem['tag'] ?></span>
                        <? endif; ?>
                        <? if (!empty($aItem['description'])): ?>
                            <? Out::out($aItem['description']) ?>
                        <? endif ?>
                    </td>
                </tr>
            <? endforeach ?>
        </table>
    <? else: ?>
        <?= Locale::getMessage('LIST_EMPTY') ?>
    <? endif ?>
    <?

}