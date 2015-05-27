<?php
$module_id = "sprint.migration";

global $APPLICATION;
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
if (!($MODULE_RIGHT >= "R")){
    $APPLICATION->AuthForm("ACCESS_DENIED");
}?>


<?=BeginNote();?>
<?echo GetMessage('SPRINT_MIGRATION_MORE_INFO')?>: <a target="_blank" href="https://bitbucket.org/andrey_ryabin/sprint.migration">https://bitbucket.org/andrey_ryabin/sprint.migration</a>
<?=EndNote();?>
