<?php
$module_id = "sprint.migration";

global $APPLICATION;
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
if (!($MODULE_RIGHT >= "R")){
    $APPLICATION->AuthForm("ACCESS_DENIED");
}

if (defined('BX_UTF') && BX_UTF === true){
    IncludeModuleLangFile(__FILE__, 'ru_utf8');
} else {
    IncludeModuleLangFile(__FILE__, 'ru');
}

CModule::IncludeModule($module_id);
$manager = new Sprint\Migration\Manager();




$aTabs = array(
    array(
        "DIV" => "edit1", "TAB" => GetMessage("SPRINT_MIGRATION_SETTINGS"), "ICON" => "pull_path", "TITLE" => GetMessage("SPRINT_MIGRATION_SETTINGS"),
    ),
);

$tabControl = new CAdminTabControl("tabControl", $aTabs);

if(isset($_POST['migration_dir']) && check_bitrix_sessid()) {

    $manager->setMigrationDir(trim($_POST['migration_dir']));
}
?>

<form method="post" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&lang=<?echo LANG?>">
    <?php echo bitrix_sessid_post()?>
    <?php
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    ?>

    <tr>
        <td><?=GetMessage("SPRINT_MIGRATION_MIGRATION_DIR")?>:</td>
        <td><input id="migration_dir" type="text" size="40" value="<?=$manager->getMigrationDir()?>" name="migration_dir"></td>
    </tr>


    <?$tabControl->Buttons();?>

    <input type="submit" name="Update" value="<?echo GetMessage('SPRINT_MIGRATION_SAVE')?>" class="adm-btn-save">
    <?$tabControl->End();?>

</form>

<?=BeginNote();?>
<?echo GetMessage('SPRINT_MIGRATION_MORE_INFO')?>: <a href="https://bitbucket.org/andrey_ryabin/sprint.migration">https://bitbucket.org/andrey_ryabin/sprint.migration</a>
<?=EndNote();?>
