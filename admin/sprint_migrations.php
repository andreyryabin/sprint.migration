<?php

define('CACHED_b_iblock_type', false);
define('CACHED_b_iblock', false);
define('CACHED_b_iblock_property_enum', false);

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

\CModule::IncludeModule("sprint.migration");

global $APPLICATION;
$APPLICATION->SetTitle(GetMessage('SPRINT_MIGRATIONS'));

$versionManager = new Sprint\Migration\VersionManager();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    CUtil::JSPostUnescape();
}

include __DIR__ .'/steps/migration_execute.php';
include __DIR__ .'/steps/migration_info.php';
include __DIR__ .'/steps/migration_list.php';
include __DIR__ .'/steps/migration_new.php';
include __DIR__ .'/steps/migration_status.php';
include __DIR__ .'/steps/migration_create.php';

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

?>
<style type="text/css">

    .c-migration-block {
        padding: 0px 0px 8px;
    }
    .c-migration-descr {
        padding: 5px 0px;
    }

    .c-migration-item-is_success,
    .c-migration-item-is_new,
    .c-migration-item-is_unknown {
        text-decoration: none;
    }

    .c-migration-item-is_success {
        color: #080;
    }

    .c-migration-item-is_new {
        color: #a00;
    }

    .c-migration-item-is_unknown {
        color: #00a;
    }
    .c-migration-adm-info {
        float: right;
    }
    .c-migration-adm-info p{
        margin: 5px 0px;padding: 0px;
    }
    .c-migration-adm-info span{
        display: inline-block;
        width: 10px;
        height: 10px;
    }
</style>

<div id="migration_progress" style="margin:0px 0px 10px 0px;"></div>

<? $tabControl1 = new CAdminTabControl("tabControl2", array(
    array("DIV" => "tab2", "TAB" => GetMessage('SPRINT_MIGRATION_TAB1'), "TITLE" => GetMessage('SPRINT_MIGRATION_TAB1_TITLE')),
));

$tabControl1->Begin();
$tabControl1->BeginNextTab();
?>
<tr>
    <td class="adm-detail-content-cell-l" style="text-align:left;vertical-align:top;width:40%;">
        &nbsp;
    </td>
    <td class="adm-detail-content-cell-r" style="vertical-align:top;width:60%">
        <div id="migration_migrations"></div>
    </td>
</tr>
<tr>
    <td class="adm-detail-content-cell-l" style="width:40%;">&nbsp;</td>
    <td class="adm-detail-content-cell-r" style="width:60%">
        <?= GetMessage('SPRINT_MIGRATION_DESCR2') ?>
        <textarea style="width: 90%" rows="3" id="migration_migration_descr" name="migration_migration_descr"></textarea>
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_GENERATE') ?>" onclick="migrationCreateMigration();">
    </td>
</tr>

<? $tabControl1->Buttons(); ?>

<input type="button" value="<?= GetMessage('SPRINT_MIGRATION_UP_START') ?>" onclick="migrationMigrationsUpConfirm();" class="adm-btn-green" />
<input type="button" value="<?= GetMessage('SPRINT_MIGRATION_DOWN_START') ?>" onclick="migrationMigrationsDownConfirm();" />

<div style="float: right" >
<input type="button" value="<?= GetMessage('SPRINT_MIGRATION_TOGGLE_LIST') ?>" onclick="migrationMigrationToggleView('list');" class="adm-btn c-migration-filter c-migration-filter-list" />
<input type="button" value="<?= GetMessage('SPRINT_MIGRATION_TOGGLE_NEW') ?>" onclick="migrationMigrationToggleView('new');" class="adm-btn c-migration-filter c-migration-filter-new" />
<input type="button" value="<?= GetMessage('SPRINT_MIGRATION_TOGGLE_STATUS') ?>" onclick="migrationMigrationToggleView('status');" class="adm-btn c-migration-filter c-migration-filter-status" />
</div>
<input type="hidden" value="<?= bitrix_sessid() ?>" name="send_sessid" />
<? $tabControl1->End(); ?>


<div class="adm-info-message-wrap c-migration-adm-info">
<div class="adm-info-message ">
    <strong>Легенда</strong><br/>
    <p>
        <span style="background: #a00;"></span>
        - <?= GetMessage('SPRINT_MIGRATION_LEGEND_NEW') ?>
    </p>
    <p>
        <span style="background: #080;"></span>
        - <?= GetMessage('SPRINT_MIGRATION_LEGEND_SUCCESS') ?>
    </p>
    <p>
        <span style="background: #00a;"></span>
        - <?= GetMessage('SPRINT_MIGRATION_LEGEND_UNKNOWN') ?>
    </p>

    <p><br/>
        <strong><?= GetMessage('SPRINT_MIGRATION_MIGRATION_DIR') ?></strong><br/>
        <?$webdir = \Sprint\Migration\Env::getMigrationWebDir()?>
        <?if ($webdir):?>
            <? $href = '/bitrix/admin/fileman_admin.php?' . http_build_query(array(
                    'lang' => LANGUAGE_ID,
                    'site' => SITE_ID,
                    'path' => $webdir
                ))?>
            <a href="<?=$href?>" target="_blank"><?=$webdir?></a>
        <?else:?>
            <?=\Sprint\Migration\Env::getMigrationDir()?>
        <?endif?>
    </p>

</div>
</div>



<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script language="JavaScript">
    function migrationMigrationsUpConfirm() {
        if (confirm('<?=GetMessage('SPRINT_MIGRATION_UP_CONFIRM')?>')) {
            migrationExecuteStep('migration_execute', {next_action: 'up'});
        }
    }

    function migrationMigrationsDownConfirm() {
        if (confirm('<?=GetMessage('SPRINT_MIGRATION_DOWN_CONFIRM')?>')) {
            migrationExecuteStep('migration_execute', {next_action  : 'down'});
        }
    }

    function migrationExecuteStep(step_code, postData, succesCallback) {
        postData = postData || {};
        postData['step_code'] = step_code;
        postData['send_sessid'] = $('input[name=send_sessid]').val();

        jQuery.ajax({
            type: "POST",
            url: '<?=pathinfo(__FILE__, PATHINFO_BASENAME)?>?lang=ru',
            dataType: "html",
            data: postData,
            success: function (result) {
                if (succesCallback) {
                    succesCallback(result)
                } else {
                    $('#migration_progress').html(result).show();
                }
            },

            error: function(result){

            }
        });
    }

    function migrationCreateMigration() {
        migrationExecuteStep('migration_create', {description: $('#migration_migration_descr').val()}, function (result) {
            $('#migration_migration_descr').val('');
            migrationMigrationRefresh();
        });
    }

    function migrationMigrationToggleView(view){
        migrationView = view;

        $('.c-migration-filter').removeClass('adm-btn-active');
        $('.c-migration-filter-' + view).addClass('adm-btn-active');

        migrationMigrationRefresh();
    }
    
    function migrationMigrationRefresh(callbackAfterRefresh) {
        migrationExecuteStep('migration_' + migrationView, {}, function (data) {
            $('#migration_migrations').empty().html(data);
            if (callbackAfterRefresh) {
                callbackAfterRefresh()
            }
        });
    }

    function migrationMigrationInfo(version) {
        migrationExecuteStep('migration_info', {version: version}, function (data) {
            $('#migration_info_' + version).empty().html(data);
        });
    }

</script>

<script language="JavaScript">
    <?
    
    $views = array('list', 'new', 'status');
    $curView = \Sprint\Migration\Env::getDbOption('admin_versions_view');
    $curView = in_array($curView, $views) ? $curView : 'list';

    ?>
    
    var migrationView = '<?=$curView?>';

    $(document).ready(function () {
        migrationMigrationToggleView(migrationView);
    });
    
</script>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php"); ?>
