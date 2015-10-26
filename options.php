<?php
$module_id = "sprint.migration";

global $APPLICATION;
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
if (!($MODULE_RIGHT >= "R")){
    $APPLICATION->AuthForm("ACCESS_DENIED");
}

CModule::IncludeModule($module_id);
$upgradeManager = new \Sprint\Migration\UpgradeManager(true);

if ($_SERVER['REQUEST_METHOD'] == "POST" && check_bitrix_sessid()){

    if (!empty($_REQUEST["upgrade_reload"])){
        $upgradeManager->upgradeReload();
    }

}

?>

<form method="post" action="<? echo $APPLICATION->GetCurPage() ?>?mid=<?=urlencode($module_id)?>&amp;lang=<?=LANGUAGE_ID?>">
    <p><?=GetMessage('SPRINT_MIGRATION_UPGRADE_VERSION')?>: <?= $upgradeManager->getUpgradeVersion() ?> </p>
    <p><input type="submit" name="upgrade_reload" value="<?=GetMessage('SPRINT_MIGRATION_UPGRADE_RELOAD')?>"></p>
    <?=bitrix_sessid_post();?>
</form>