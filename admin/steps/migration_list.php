<?php

use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
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
    ($_POST["step_code"] == "migration_view_modified") ||
    ($_POST["step_code"] == "migration_view_older") ||
    ($_POST["step_code"] == "migration_view_installed")
);

if ($listView && check_bitrix_sessid('send_sessid')) {

    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $search = !empty($_POST['search']) ? trim($_POST['search']) : '';
    $search = Sprint\Migration\Locale::convertToUtf8IfNeed($search);

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
    } elseif ($_POST["step_code"] == "migration_view_modified") {
        $versions = $versionManager->getVersions([
            'search' => $search,
            'modified' => 1,
        ]);
    } elseif ($_POST["step_code"] == "migration_view_older") {
        $versions = $versionManager->getVersions([
            'search' => $search,
            'older' => 1,
        ]);
    } else {
        $versions = $versionManager->getVersions([
            'search' => $search,
        ]);
    }

    $webdir = $versionManager->getWebDir();

    $getOnclickMenu = function ($item) use ($webdir, $versionConfig) {
        $menu = [];

        if ($item['status'] == VersionEnum::STATUS_NEW) {
            $menu[] = [
                'TEXT' => Locale::getMessage('UP'),
                'ONCLICK' => 'migrationMigrationUp(\'' . $item['version'] . '\')',
            ];
            $menu[] = [
                'TEXT' => Locale::getMessage('MARK_NEW_AS_INSTALLED'),
                'ONCLICK' => 'migrationMigrationMark(\'' . $item['version'] . '\',\'' . VersionEnum::STATUS_INSTALLED . '\')',
            ];
        }
        if ($item['status'] == VersionEnum::STATUS_INSTALLED) {
            $menu[] = [
                'TEXT' => Locale::getMessage('DOWN'),
                'ONCLICK' => 'migrationMigrationDown(\'' . $item['version'] . '\')',
            ];
            $menu[] = [
                'TEXT' => Locale::getMessage('SETTAG'),
                'ONCLICK' => 'migrationMigrationSetTag(\'' . $item['version'] . '\',\'' . $item['tag'] . '\')',
            ];
            $menu[] = [
                'TEXT' => Locale::getMessage('MARK_INSTALLED_AS_NEW'),
                'ONCLICK' => 'migrationMigrationMark(\'' . $item['version'] . '\',\'' . VersionEnum::STATUS_NEW . '\')',
            ];
        }

        if ($item['status'] == VersionEnum::STATUS_UNKNOWN) {
            $menu[] = [
                'TEXT' => Locale::getMessage('SETTAG'),
                'ONCLICK' => 'migrationMigrationSetTag(\'' . $item['version'] . '\')',
            ];
        }

        if ($item['status'] != VersionEnum::STATUS_UNKNOWN && $webdir) {
            $viewUrl = '/bitrix/admin/fileman_file_view.php?' . http_build_query([
                    'lang' => LANGUAGE_ID,
                    'site' => SITE_ID,
                    'path' => $webdir . '/' . $item['version'] . '.php',
                ]);

            $menu[] = [
                'TEXT' => Locale::getMessage('VIEW_FILE'),
                'LINK' => $viewUrl,
            ];
        }

        $transferMenu = [];

        $configList = $versionConfig->getList();
        foreach ($configList as $configItem) {
            if ($configItem['name'] != $versionConfig->getName()) {
                $transferMenu[] = [
                    'TEXT' => $configItem['title'],
                    'ONCLICK' => 'migrationMigrationTransfer(\'' . $item['version'] . '\',\'' . $configItem['name'] . '\')',
                ];
            }
        }

        if (!empty($transferMenu)) {
            $menu[] = [
                'TEXT' => Locale::getMessage('TRANSFER_TO'),
                'MENU' => $transferMenu,
            ];
        }


        $menu[] = [
            'TEXT' => Locale::getMessage('DELETE'),
            'ONCLICK' => 'migrationMigrationDelete(\'' . $item['version'] . '\')',
        ];

        return CUtil::PhpToJSObject($menu);
    }

    ?>
    <?php if (!empty($versions)): ?>
        <table class="sp-list">
            <?php foreach ($versions as $item): ?>
                <tr>
                    <td class="sp-list-td__buttons">
                        <a onclick="this.blur();BX.adminShowMenu(this, <?= $getOnclickMenu($item) ?>, {active_class: 'adm-btn-active',public_frame: '0'}); return false;"
                           href="javascript:void(0)"
                           class="adm-btn"
                           hidefocus="true">&equiv;</a>
                    </td>
                    <td class="sp-list-td__content">
                        <span class="sp-item-<?= $item['status'] ?>"><?= $item['version'] ?></span>
                        <?php if ($item['modified']): ?>
                            <span class="sp-modified" title="<?= Locale::getMessage('MODIFIED_VERSION') ?>">
                                <?= Locale::getMessage('MODIFIED_LABEL') ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($item['older']): ?>
                            <span class="sp-older" title="<?= Locale::getMessage('OLDER_VERSION', [
                                '#V1#' => $item['older'],
                                '#V2#' => Module::getVersion(),
                            ]) ?>">
                                <?= Locale::getMessage('OLDER_LABEL') ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($item['tag']): ?>
                            <span class="sp-tag" title="<?= Locale::getMessage('TAG') ?>">
                                <?= $item['tag'] ?>
                            </span>
                        <?php endif; ?>
                        <?php if ($item['status'] == VersionEnum::STATUS_NEW) { ?>
                            <?php Out::out(Locale::getMessage('VERSION_NEW')) ?>
                        <?php } ?>
                        <?php if ($item['status'] == VersionEnum::STATUS_INSTALLED) { ?>
                            <?php Out::out(Locale::getMessage('VERSION_INSTALLED')) ?>
                        <?php } ?>
                        <?php if (!empty($item['description'])): ?>
                            <?= Out::prepareToHtml(
                                $item['description'],
                                [
                                    'tracker_task_url' => $versionConfig->getVal('tracker_task_url'),
                                ]
                            ) ?>
                        <?php endif ?>
                    </td>
                </tr>
            <?php endforeach ?>
        </table>
    <?php else: ?>
        <?= Locale::getMessage('LIST_EMPTY') ?>
    <?php endif ?>
    <?php
}
