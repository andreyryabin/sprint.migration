<?php

$search = '';
$listview = 'list';
$addtag = '';


?>
<div id="migration-container" data-sessid="<?= bitrix_sessid() ?>">
    <div class="sp-group">
        <div class="sp-group-row2">
            <div class="sp-block sp-block-scroll sp-white">
                <div id="migration_migrations" class="sp-scroll"></div>
            </div>
            <div class="sp-block sp-block-scroll">
                <div id="migration_progress" class="sp-scroll"></div>
            </div>
        </div>
    </div>
    <div class="sp-group">
        <div class="sp-group-row2">
            <div class="sp-block">
                <input type="button"
                       value="<?= GetMessage('SPRINT_MIGRATION_UP_START') ?>"
                       onclick="migrationMigrationsUpConfirm();"
                       class="adm-btn-green"/>
                <input type="button"
                       value="<?= GetMessage('SPRINT_MIGRATION_DOWN_START') ?>"
                       onclick="migrationMigrationsDownConfirm();"/>
                <span title="<?= GetMessage('SPRINT_MIGRATION_ADDTAG_TITLE') ?>">
                <?= GetMessage('SPRINT_MIGRATION_ADDTAG') ?>
                <input placeholder="<?= GetMessage('SPRINT_MIGRATION_ADDTAG_TAG') ?>"
                       style="width: 100px;"
                       type="text"
                       value="<?= $addtag ?>"
                       class="adm-input"
                       name="migration_addtag"/>
                    </span>
            </div>
            <div class="sp-block">
                <input placeholder="<?= GetMessage('SPRINT_MIGRATION_SEARCH') ?>"
                       style=""
                       type="text"
                       value="<?= $search ?>"
                       class="adm-input"
                       name="migration_search"/>
                <select class="sp-stat">
                    <option <? if ($listview == 'list'): ?>selected="selected"<? endif ?>
                            value="list"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_LIST') ?></option>
                    <option <? if ($listview == 'new'): ?>selected="selected"<? endif ?>
                            value="new"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_NEW') ?></option>
                    <option <? if ($listview == 'installed'): ?>selected="selected"<? endif ?>
                            value="installed"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_INSTALLED') ?></option>
                    <option <? if ($listview == 'status'): ?>selected="selected"<? endif ?>
                            value="status"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_STATUS') ?></option>
                </select>
                <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_SEARCH') ?>" class="sp-search"/>

            </div>
        </div>
    </div>
    <div class="sp-separator"></div>
    <? foreach (['default', 'configurator'] as $builderGroup): ?>
        <? include __DIR__ . '/builder_group.php' ?>
    <? endforeach ?>

    <div class="sp-separator"></div>
</div>