<?php

use Sprint\Migration\Locale;
use Sprint\Migration\VersionConfig;

/** @var $versionConfig VersionConfig */

$getOnclickMenu = function () {
    $menu = [];
    $menu[] = [
        'TEXT'    => Locale::getMessage('UP_START_WITH_TAG'),
        'ONCLICK' => 'migrationMigrationsUpWithTag()',
    ];
    $menu[] = [
        'TEXT'    => Locale::getMessage('DOWN_START'),
        'ONCLICK' => 'migrationMigrationsDownConfirm()',
    ];
    $menu[] = [
        'TEXT'    => Locale::getMessage('DELETE_UNKNOWN'),
        'ONCLICK' => 'migrationMigrationsDeleteUnknownConfirm()',
    ];
    return CUtil::PhpToJSObject($menu);
}
?>
<div id="migration_container" data-sessid="<?= bitrix_sessid() ?>" data-config="<?= $versionConfig->getName() ?>">
    <div class="sp-table">
        <div class="sp-row2">
            <div class="sp-col sp-col-scroll sp-white">
                <div id="migration_migrations" class="sp-scroll"></div>
            </div>
            <div class="sp-col sp-col-scroll">
                <div id="migration_progress" class="sp-scroll"></div>
            </div>
        </div>
    </div>
    <div class="sp-table">
        <div class="sp-row2">
            <div class="sp-col">
                <input type="button"
                       value="<?= Locale::getMessage('UP_START') ?>"
                       onclick="migrationMigrationsUpConfirm();"
                       class="adm-btn-green"/>
                <a onclick="this.blur();BX.adminShowMenu(this, <?= $getOnclickMenu() ?>, {active_class: 'adm-btn-active',public_frame: '0'}); return false;"
                   href="javascript:void(0)"
                   class="adm-btn"
                   hidefocus="true">&equiv;</a>

                <div id="migration_actions" style="float: right"></div>
            </div>
            <div class="sp-col">
                <input placeholder="<?= Locale::getMessage('SEARCH') ?>"
                       style=""
                       type="text"
                       value=""
                       class="adm-input"
                       id="migration_search"/>
                <select id="migration_view">
                    <option value="migration_view_all"><?= Locale::getMessage('TOGGLE_LIST') ?></option>
                    <option value="migration_view_new"><?= Locale::getMessage('TOGGLE_NEW') ?></option>
                    <option value="migration_view_installed"><?= Locale::getMessage('TOGGLE_INSTALLED') ?></option>
                    <option value="migration_view_unknown"><?= Locale::getMessage('TOGGLE_UNKNOWN') ?></option>
                    <option value="migration_view_tag"><?= Locale::getMessage('TOGGLE_TAG') ?></option>
                    <option value="migration_view_modified"><?= Locale::getMessage('TOGGLE_MODIFIED') ?></option>
                    <option value="migration_view_older"><?= Locale::getMessage('TOGGLE_OLDER') ?></option>
                    <option value="migration_view_status"><?= Locale::getMessage('TOGGLE_STATUS') ?></option>
                </select>
                <input id="migration_refresh" type="button" value="<?= Locale::getMessage('SEARCH') ?>"/>
            </div>
        </div>
    </div>
    <div class="sp-separator"></div>
    <?php include __DIR__ . '/builder_group.php' ?>
    <div class="sp-separator"></div>
</div>
