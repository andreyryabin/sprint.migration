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
                    <?php include __DIR__ . '/search.php' ?>
                </div>
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
            </div>
            <div class="sp-col">
                <div id="migration_actions" style="float: right"></div>
            </div>
        </div>
    </div>
    <div class="sp-separator"></div>
    <?php include __DIR__ . '/builder_group.php' ?>
    <div class="sp-separator"></div>
</div>
