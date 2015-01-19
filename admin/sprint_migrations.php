<?
require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");
IncludeModuleLangFile(__FILE__);

global $APPLICATION;
$APPLICATION->SetTitle(GetMessage('SPRINT_MIGRATIONS'));

CModule::IncludeModule("sprint.migration");

$manager = new Sprint\Migration\Manager();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    CUtil::JSPostUnescape();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migrations_up" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $success = $manager->executeMigrateUp(1);

    ?>
    <?if ($success): ?>
        <script>
            migrationExecuteStep('migration_list', {}, function (data) {
                $('#migration_migrations').empty().html(data);
                migrationExecuteStep('migrations_up', {});
            });
        </script>
    <?else: ?>
        <script>
            migrationMigrationList();
        </script>
    <?endif ?>

    <?
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migrations_down" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $success = $manager->executeMigrateDown(1);

    ?>
    <?if ($success == 1): ?>
        <script>
            migrationExecuteStep('migration_list', {}, function (data) {
                $('#migration_migrations').empty().html(data);
                migrationExecuteStep('migrations_down', {});
            });
        </script>
    <?else: ?>
        <script>
            migrationMigrationList();
        </script>
    <?endif ?>

    <?
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_execute" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $version = isset($_POST['version']) ? $_POST['version'] : 0;
    $up = !empty($_POST['up']) ? true : false;

    $success = $manager->executeVersion($version, $up);

    ?>

    <script>
        migrationMigrationList();
    </script>

    <?
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_descr" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $version = isset($_POST['version']) ? $_POST['version'] : 0;
    $descr = $manager->getVersionDescription($version);
    $descr = !empty($descr) ? $descr : GetMessage('SPRINT_MIGRATION_NO_DESCRSPRINT_MIGRATIONS');
    ?>
    <div class="c-migration-descr"><?= $descr ?></div>
    <?

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_list" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $versions = $manager->getVersions();

    ?>
    <?if (!empty($versions)): ?>

        <?foreach ($versions as $aItem): ?>

            <div class="c-migration-block">
                <a href="#" title="<?= GetMessage('SPRINT_MIGRATION_DESCR1') ?>"
                   onclick="migrationMigrationDescr('<?= $aItem['version'] ?>');return false;"
                   class="c-migration-item-<?= $aItem['type'] ?>">
                    <span><?= $aItem['version'] ?></span>
                </a>
                <?if ($aItem['type'] == 'is_new'): ?>
                    <input onclick="migrationExecute('<?= $aItem['version'] ?>', 1);" value="выполнить" type="button">
                <?endif ?>
                <?if ($aItem['type'] == 'is_success'): ?>
                    <input onclick="migrationExecute('<?= $aItem['version'] ?>', 0);" value="откатить" type="button">
                <?endif ?>
                <div id="migration_item_<?= $aItem['version'] ?>_descr"></div>
            </div>

        <?endforeach ?>
    <?else: ?>
        <?= GetMessage('SPRINT_MIGRATION_LIST_EMPTY') ?>
    <?endif ?>
    <?
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_create" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $description = isset($_POST['description']) ? $_POST['description'] : 0;
    $manager->createVersionFile($description);

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}

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
    .c-migration-block .c-migration-item-is_404 {
        color: #000;
    }

    .c-migration-block .c-migration-item-is_success {
        color: #080;
    }

    .c-migration-block .c-migration-item-is_new {
        color: #a00;
    }

    .c-migration-block .c-migration-item-is_404 {
        color: #00a;
    }
</style>
<div id="migration_progress" style="margin:0px"></div>

<? $tabControl1 = new CAdminTabControl("tabControl2", array(
    array("DIV" => "tab2", "TAB" => GetMessage('SPRINT_MIGRATION_TAB1'), "TITLE" => GetMessage('SPRINT_MIGRATION_LIST1')),
));

$tabControl1->Begin();
$tabControl1->BeginNextTab();
?>
<tr>
    <td class="adm-detail-content-cell-l" style="width:40%;">&nbsp;</td>
    <td class="adm-detail-content-cell-r" style="width:60%">
        <div id="migration_migrations"></div>
    </td>
</tr>
<tr>
    <td class="adm-detail-content-cell-l" style="width:40%;">&nbsp;</td>
    <td class="adm-detail-content-cell-r" style="width:60%">
        <?= GetMessage('SPRINT_MIGRATION_DESCR2') ?>
        <textarea style="width: 90%" rows="3" id="migration_migration_descr"
                  name="migration_migration_descr"></textarea>
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_GENERATE') ?>" onclick="migrationCreateMigration();" class="button">
    </td>
</tr>
<? $tabControl1->Buttons(); ?>


<input type="button" value="<?= GetMessage('SPRINT_MIGRATION_UP') ?>" onclick="migrationMigrationsUpConfirm();"
       class="adm-btn-save">
<input type="button" value="<?= GetMessage('SPRINT_MIGRATION_DOWN') ?>" onclick="migrationMigrationsDownConfirm();"
       class="button">
<input type="hidden" value="<?= bitrix_sessid() ?>" name="send_sessid">
<? $tabControl1->End(); ?>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
<script language="JavaScript">

    function migrationMigrationsUpConfirm() {
        if (confirm('<?=GetMessage('SPRINT_MIGRATION_UP_CONFIRM')?>')) {
            migrationLockButtons();
            migrationExecuteStep('migrations_up', {});
        }
    }

    function migrationMigrationsDownConfirm() {
        if (confirm('<?=GetMessage('SPRINT_MIGRATION_DOWN_CONFIRM')?>')) {
            migrationLockButtons();
            migrationExecuteStep('migrations_down', {});
        }
    }

    function migrationExecute(version, up) {
        migrationLockButtons();
        migrationExecuteStep('migration_execute', {version: version, up: up});
    }

    function migrationExecuteStep(step_code, postData, succesCallback) {
        migrationLockButtons();

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

            }
        });
    }

    function migrationUnlockButtons() {
        CloseWaitWindow();
        $('.adm-btn-save').attr('disabled', false);
    }

    function migrationLockButtons() {
        ShowWaitWindow();
        $('.adm-btn-save').attr('disabled', true);

    }

    function migrationCreateMigration() {
        migrationLockButtons();
        migrationExecuteStep('migration_create', {description: $('#migration_migration_descr').val()}, function (data) {
            $('#migration_migration_descr').val('');
            migrationMigrationList();
        });
    }

    function migrationMigrationList() {
        migrationLockButtons();
        migrationExecuteStep('migration_list', {}, function (data) {
            $('#migration_migrations').empty().html(data);
            migrationUnlockButtons();
        });
    }

    function migrationMigrationDescr(version) {
        migrationLockButtons();
        migrationExecuteStep('migration_descr', {version: version}, function (data) {
            $('#migration_item_' + version + '_descr').empty().html(data);
            migrationUnlockButtons();
        });
    }

</script>

<script language="JavaScript">
    $(document).ready(function () {

        migrationMigrationList();

    });
</script>

<? require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php"); ?>
