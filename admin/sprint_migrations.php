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

include __DIR__ .'/steps/migration_execute.php';
include __DIR__ .'/steps/migration_list.php';
include __DIR__ .'/steps/migration_status.php';
include __DIR__ .'/steps/migration_mark.php';
include __DIR__ .'/steps/migration_create.php';

/** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

\CUtil::InitJSCore(Array("jquery"));

?>

<input type="hidden" value="<?= bitrix_sessid() ?>" name="send_sessid" />

<style type="text/css">
<? include __DIR__ . '/assets/style.css' ?>
</style>

<div id="migration-container">

<? $tabControl1 = new CAdminTabControl("tabControl2", array(
    array("DIV" => "tab1", "TAB" => GetMessage('SPRINT_MIGRATION_TAB1'), "TITLE" => GetMessage('SPRINT_MIGRATION_TAB1_TITLE')),
    array("DIV" => "tab3", "TAB" => GetMessage('SPRINT_MIGRATION_TAB3'), "TITLE" => GetMessage('SPRINT_MIGRATION_TAB3_TITLE')),
));

$tabControl1->Begin();
$tabControl1->BeginNextTab();
?>
<tr>
    <td style="vertical-align: top;">
        <div id="migration_migrations"></div>
    </td>
</tr>
<?$tabControl1->BeginNextTab();?>
<tr>
    <td style="vertical-align: top;">
        <div id="migration_progress" style="overflow-x:auto;overflow-y: scroll;max-height: 320px;"></div>
    </td>
</tr>
<? $tabControl1->Buttons(); ?>
    <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_UP_START') ?>" onclick="migrationMigrationsUpConfirm();" class="adm-btn-green" />
    <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_DOWN_START') ?>" onclick="migrationMigrationsDownConfirm();" />

    <div class="c-migration-filter">
        <? $search = \Sprint\Migration\Module::getDbOption('admin_versions_search', '');?>
        <input placeholder="<?= GetMessage('SPRINT_MIGRATION_SEARCH') ?>" style="" type="text" value="<?=$search?>" class="adm-input" name="migration_search"/>

        <? $view = \Sprint\Migration\Module::getDbOption('admin_versions_view', 'list');?>
        <select class="c-migration-stat">
            <option <?if ($view == 'list'):?>selected="selected"<?endif?> value="list"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_LIST') ?></option>
            <option <?if ($view == 'new'):?>selected="selected"<?endif?> value="new"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_NEW') ?></option>
            <option <?if ($view == 'installed'):?>selected="selected"<?endif?> value="installed"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_INSTALLED') ?></option>
            <option <?if ($view == 'status'):?>selected="selected"<?endif?> value="status"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_STATUS') ?></option>
        </select>
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_SEARCH') ?>" class="c-migration-search" />
    </div>

<? $tabControl1->End(); ?>
<div class="c-migration-block">
    <div class="c-migration-block_title"><?= GetMessage('SPRINT_MIGRATION_CREATE') ?></div>

    <p>
        <?= GetMessage('SPRINT_MIGRATION_FORM_PREFIX') ?><br/>
        <input type="text" style="width: 250px;" id="migration_migration_prefix" value="<?=$versionManager->getConfigVal('version_prefix')?>" />
    </p>
    <p>
        <?= GetMessage('SPRINT_MIGRATION_FORM_DESCR') ?> <br/>
        <textarea rows="3" style="width: 350px;" id="migration_migration_descr"></textarea>
    </p>
    <p>
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_GENERATE') ?>" onclick="migrationCreateMigration();" />
    </p>

    <div id="migration_migration_create_result"></div>
</div>

<div class="c-migration-block">
    <div class="c-migration-block_title"><?= GetMessage('SPRINT_MIGRATION_MARK') ?></div>
    <p>
        <input type="text" style="width: 250px;" id="migration_migration_mark" value="" />
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_MARK_AS_INSTALLED') ?>" onclick="migrationMarkMigration('installed');" />
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_MARK_AS_NEW') ?>" onclick="migrationMarkMigration('new');" />
    </p>
    <span id="migration_migration_mark_result"></span>
</div>

<div class="c-migration-block">
    <div class="c-migration-block_title"><?= GetMessage('SPRINT_MIGRATION_CONFIG_LIST') ?></div>
    <?php
    $configList = $versionManager->getConfigList();
    $configName = $versionManager->getConfigName();
    ?><?php foreach ($configList as $configItem) :?>
    <table class="c-migration-config">
        <thead>
        <tr>
            <td colspan="2">
                <?if ($configItem['name'] == $configName):?>
                    <strong><?=$configItem['title']?> *</strong>
                <?else:?>
                    <form method="get" action="">
                        <strong><?=$configItem['title']?></strong> &nbsp;
                        <input name="config" type="hidden" value="<?=$configItem['name']?>">
                        <input name="lang" type="hidden" value="<?=LANGUAGE_ID?>">
                        <input type="submit" value="<?=GetMessage('SPRINT_MIGRATION_CONFIG_SWITCH')?>">
                    </form>
                <?endif?>
            </td>
        </tr>
        </thead>
        <tbody>
        <? foreach ($configItem['values'] as $key => $val) :?>
            <tr>
                <td><?=$key?></td>
                <td><?=$val?></td>
            </tr>
        <?endforeach;?>
        </tbody>
    </table>
    <?endforeach;?>
</div>

<div class="c-migration-block">
    <div class="c-migration-block_title"><?= GetMessage('SPRINT_MIGRATION_HELP_DOC') ?></div>
    <p>
        <a href="https://bitbucket.org/andrey_ryabin/sprint.migration" target="_blank">https://bitbucket.org/andrey_ryabin/sprint.migration</a>
    </p>
</div>
</div>
<script type="text/javascript">
    function migrationMigrationsUpConfirm() {
        if (confirm('<?=GetMessage('SPRINT_MIGRATION_UP_CONFIRM')?>')) {
            migrationExecuteStep('migration_execute', {
                'next_action': 'up'
            });
        }
    }

    function migrationMigrationsDownConfirm() {
        if (confirm('<?=GetMessage('SPRINT_MIGRATION_DOWN_CONFIRM')?>')) {
            migrationExecuteStep('migration_execute', {
                'next_action'  : 'down'
            });
        }
    }

    function migrationOutProgress(result) {
        var outProgress = $('#migration_progress');
        var lastOutElem = outProgress.children('div').last();
        if (lastOutElem.hasClass('migration-bar') && $(result).first().hasClass('migration-bar')){
            lastOutElem.replaceWith(result);
        } else {
            outProgress.append(result);
            outProgress.scrollTop(outProgress.prop("scrollHeight"));
        }
    }

    function migrationExecuteStep(step_code, postData, succesCallback) {
        postData = postData || {};
        postData['step_code'] = step_code;
        postData['send_sessid'] = $('input[name=send_sessid]').val();
        postData['search'] = $('input[name=migration_search]').val();

        migrationEnableButtons(0);

        jQuery.ajax({
            type: "POST",
            dataType: "html",
            data: postData,
            success: function (result) {
                if (succesCallback) {
                    succesCallback(result)
                } else {
                    migrationOutProgress(result);
                }
            },
            error: function(result){

            }
        });
    }

    function migrationEnableButtons(enable) {
        var buttons = $('#migration-container').find('input,select');
        if (enable == 1){
            buttons.removeAttr('disabled');
        } else {
            buttons.attr('disabled', 'disabled');
        }
    }

    function migrationCreateMigration() {
        $('#migration_migration_create_result').html('');
        migrationExecuteStep('migration_create', {
            description: $('#migration_migration_descr').val(),
            prefix: $('#migration_migration_prefix').val()
        }, function (result) {
            $('#migration_migration_descr').val('');
            $('#migration_migration_create_result').html(result);
            migrationMigrationRefresh();
        });
    }

    function migrationMarkMigration(status) {
        $('#migration_migration_mark_result').html('');
        migrationExecuteStep('migration_mark', {
            version: $('#migration_migration_mark').val(),
            status: status
        }, function (result) {
            $('#migration_migration_mark_result').html(result);
            migrationMigrationRefresh();
        });
    }

    function migrationMigrationRefresh(callbackAfterRefresh) {
        var view = $('.c-migration-stat').val();
        migrationExecuteStep('migration_' + view, {}, function (data) {
            $('#migration_migrations').empty().html(data);
            if (callbackAfterRefresh) {
                callbackAfterRefresh()
            } else {
                migrationEnableButtons(1);
            }
        });
    }

</script>

<script type="text/javascript">
    $(document).ready(function () {
        migrationMigrationRefresh(function(){
            migrationEnableButtons(1);
        });

        $('#tab_cont_tab3').on('click', function(){
            var outProgress = $('#migration_progress');
            outProgress.scrollTop(outProgress.prop("scrollHeight"));
        });

        $('.c-migration-stat').on('change',function(){
            migrationMigrationRefresh(function(){
                migrationEnableButtons(1);
                $('#tab_cont_tab1').click();
            });
        });

        $('input[name=migration_search]').on('keypress', function(e){
            if(e.keyCode==13){
                migrationMigrationRefresh(function(){
                    migrationEnableButtons(1);
                    $('#tab_cont_tab1').click();
                });
            }
        });

        $('.c-migration-search').on('click', function(){
            migrationMigrationRefresh(function(){
                migrationEnableButtons(1);
                $('#tab_cont_tab1').click();
            });
        });

    });
    
</script>

<? /** @noinspection PhpIncludeInspection */
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
?>