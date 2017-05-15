<?php

/** @noinspection PhpIncludeInspection */
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

\CModule::IncludeModule("sprint.migration");

/** @global $APPLICATION \CMain */

global $APPLICATION;
$APPLICATION->SetTitle(GetMessage('SPRINT_MIGRATION_TITLE'));

if ($APPLICATION->GetGroupRight("sprint.migration") == "D") {
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    CUtil::JSPostUnescape();
}

$configName = !empty($_GET['config']) ? $_GET['config'] : '';
$versionManager = new Sprint\Migration\VersionManager($configName);

include __DIR__ . '/steps/migration_execute.php';
include __DIR__ . '/steps/migration_list.php';
include __DIR__ . '/steps/migration_status.php';
include __DIR__ . '/steps/migration_mark.php';
include __DIR__ . '/steps/migration_create.php';

/** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

\CUtil::InitJSCore(Array("jquery")); ?>
    <input type="hidden" value="<?= bitrix_sessid() ?>" name="send_sessid"/>
    <div id="migration-container">
        <? $tabControl1 = new CAdminTabControl("tabControl2", array(
            array(
                "DIV" => "tab1",
                "TAB" => GetMessage('SPRINT_MIGRATION_TAB1'),
                "TITLE" => GetMessage('SPRINT_MIGRATION_TAB1_TITLE')
            ),
            array(
                "DIV" => "tab3",
                "TAB" => GetMessage('SPRINT_MIGRATION_TAB3'),
                "TITLE" => GetMessage('SPRINT_MIGRATION_TAB3_TITLE')
            ),
        ));

        $tabControl1->Begin();
        $tabControl1->BeginNextTab();
        ?>
        <tr>
            <td style="vertical-align: top;">
                <div id="migration_migrations"></div>
            </td>
        </tr>
        <? $tabControl1->BeginNextTab(); ?>
        <tr>
            <td style="vertical-align: top;">
                <div id="migration_progress" style="overflow-x:auto;overflow-y: scroll;max-height: 320px;"></div>
            </td>
        </tr>
        <? $tabControl1->Buttons(); ?>
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_UP_START') ?>"
               onclick="migrationMigrationsUpConfirm();" class="adm-btn-green"/>
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_DOWN_START') ?>"
               onclick="migrationMigrationsDownConfirm();"/>
        <div class="sp-filter">
            <? $search = \Sprint\Migration\Module::getDbOption('admin_versions_search', ''); ?>
            <input placeholder="<?= GetMessage('SPRINT_MIGRATION_SEARCH') ?>" style="" type="text"
                   value="<?= $search ?>" class="adm-input" name="migration_search"/>
            <? $view = \Sprint\Migration\Module::getDbOption('admin_versions_view', 'list'); ?>
            <select class="sp-stat">
                <option <? if ($view == 'list'): ?>selected="selected"<? endif ?>
                        value="list"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_LIST') ?></option>
                <option <? if ($view == 'new'): ?>selected="selected"<? endif ?>
                        value="new"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_NEW') ?></option>
                <option <? if ($view == 'installed'): ?>selected="selected"<? endif ?>
                        value="installed"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_INSTALLED') ?></option>
                <option <? if ($view == 'status'): ?>selected="selected"<? endif ?>
                        value="status"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_STATUS') ?></option>
            </select>
            <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_SEARCH') ?>" class="sp-search"/>
        </div>
        <? $tabControl1->End(); ?>

        <? $builders = $versionManager->getVersionBuilders() ?>
        <? foreach ($builders as $builderName => $builderClass): ?>
            <div class="sp-block">
                <? $builder = $versionManager->createVersionBuilder($builderName) ?>
                <div class="sp-block_title"><?= $builder->getTitle() ?></div>
                <div class="sp-block_body"><? include __DIR__ . '/includes/builder_form.php' ?></div>
            </div>
        <? endforeach; ?>

        <div class="sp-block">
            <div class="sp-block_title"><?= GetMessage('SPRINT_MIGRATION_MARK') ?></div>
            <div class="sp-block_body"><? include __DIR__ . '/includes/mark_form.php' ?></div>
        </div>

        <div class="sp-block">
            <div class="sp-block_title"><?= GetMessage('SPRINT_MIGRATION_CONFIG_LIST') ?></div>
            <div class="sp-block_body"><? include __DIR__ . '/includes/config_list.php' ?></div>
        </div>

        <div class="sp-block">
            <p>
                <?= GetMessage('SPRINT_MIGRATION_HELP_DOC') ?>:
                <a href="https://bitbucket.org/andrey_ryabin/sprint.migration" target="_blank">https://bitbucket.org/andrey_ryabin/sprint.migration</a>
            </p>
        </div>


    </div>
    <style type="text/css">
        <? include __DIR__ . '/assets/style.css' ?>
    </style>
    <script type="text/javascript">
        <? include __DIR__ . '/assets/script.js' ?>
    </script>
<? /** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>