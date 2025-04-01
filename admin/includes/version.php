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
    return CUtil::PhpToJSObject($menu);
}
?>
<div id="migration_container" data-sessid="<?= bitrix_sessid() ?>" data-config="<?= $versionConfig->getName() ?>">
    <div class="sp-table">
        <div class="sp-row2">
            <div class="sp-col sp-col-scroll sp-white">
                <div class="sp-search">
                    <input id="migration_search" placeholder="<?= Locale::getMessage('SEARCH') ?>" type="text" value="" class="adm-input"/>
                    <select id="migration_view">
                        <option value="migration_view_actual"><?= Locale::getMessage('TOGGLE_ACTUAL') ?></option>
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
                <div id="migration_migrations"></div>
            </div>
            <div class="sp-col sp-col-scroll" id="migration_progress"></div>
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
                <div id="migration_loading"><?= Locale::getMessage('LOADING_TEXT')?></div>
            </div>
            <div class="sp-col" id="migration_actions"></div>
        </div>
    </div>
    <div class="sp-separator"></div>
    <?php include __DIR__ . '/builder_group.php' ?>
    <div class="sp-separator"></div>
</div>
