<?php
$module_id = "sprint.migration";

global $APPLICATION;
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
if (!($MODULE_RIGHT >= "R")){
    $APPLICATION->AuthForm("ACCESS_DENIED");
}

CModule::IncludeModule($module_id);

if ($_SERVER['REQUEST_METHOD'] == "POST" && check_bitrix_sessid()){
    if (!empty($_REQUEST["remove_options"])){
        \Sprint\Migration\Module::removeDbOptions();
    }
}
?>

<p>
    <a href="/bitrix/admin/sprint_migrations.php?lang=<?=LANGUAGE_ID?>"><?= GetMessage('SPRINT_MIGRATION_GOTO_MIGRATION') ?></a>
</p>
<p>
    <a href="https://bitbucket.org/andrey_ryabin/sprint.migration" target="_blank"><?= GetMessage('SPRINT_MIGRATION_HELP_DOC') ?></a>
</p>


<br/>

<form method="post" action="">
    <p></p>
    <p><input type="submit" name="remove_options" value="<?=GetMessage('SPRINT_MIGRATION_REMOVE_OPTIONS')?>"></p>
    <?=bitrix_sessid_post();?>
</form>