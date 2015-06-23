<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

\CModule::IncludeModule("sprint.migration");

global $APPLICATION;
$APPLICATION->SetTitle(GetMessage('SPRINT_MIGRATIONS'));

$manager = new Sprint\Migration\Manager();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    CUtil::JSPostUnescape();
}

include __DIR__ .'/steps/migration_execute.php';
include __DIR__ .'/steps/migration_descr.php';
include __DIR__ .'/steps/migration_list.php';
include __DIR__ .'/steps/migration_new.php';
include __DIR__ .'/steps/migration_summary.php';
include __DIR__ .'/steps/migration_create.php';

require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

?>
<style type="text/css">
    #migration_migrations p {
        margin: 0px;
        padding: 0px 0px 10px 0px;
    }

    .c-migration-block {
        padding: 0px 0px 8px;
    }

    .c-migration-descr {
        padding: 5px 0px;
    }

    .c-migration-block a {
        text-decoration: none;
        margin: 0px 5px 0px 0px;
    }

    .c-migration-block .c-migration-item-is_success,
    .c-migration-block .c-migration-item-is_new,
    .c-migration-block .c-migration-item-is_unknown {
        color: #000;
    }

    .c-migration-block .c-migration-item-is_success {
        color: #080;
    }

    .c-migration-block .c-migration-item-is_new {
        color: #a00;
    }

    .c-migration-block .c-migration-item-is_unknown {
        color: #00a;
    }
</style>

<div id="migration_progress" style="margin:0px 0px 10px 0px;"></div>

<? $tabControl1 = new CAdminTabControl("tabControl2", array(
    array("DIV" => "tab2", "TAB" => GetMessage('SPRINT_MIGRATION_TAB1'), "TITLE" => GetMessage('SPRINT_MIGRATION_LIST1')),
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
<input type="button" value="<?= GetMessage('SPRINT_MIGRATION_TOGGLE_SUMMARY') ?>" onclick="migrationMigrationToggleView('summary');" class="adm-btn c-migration-filter c-migration-filter-summary" />
</div>
<input type="hidden" value="<?= bitrix_sessid() ?>" name="send_sessid" />
<? $tabControl1->End(); ?>

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
        migrationExecuteStep('migration_create', {description: $('#migration_migration_descr').val()}, function (data) {
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

    function migrationMigrationDescr(version) {
        migrationExecuteStep('migration_descr', {version: version}, function (data) {
            $('#migration_item_' + version + '_descr').empty().html(data);
        });
    }

</script>

<script language="JavaScript">
    <?
    
    $views = array('list', 'new', 'summary');
    $curView = \COption::GetOptionString('sprint.migration', 'admin_versions_view');
    $curView = in_array($curView, $views) ? $curView : 'list';
    
    ?>
    
    var migrationView = '<?=$curView?>';

    $(document).ready(function () {
        migrationMigrationToggleView(migrationView);
    });
    
</script>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php"); ?>
