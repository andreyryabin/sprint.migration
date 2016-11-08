<?php
$module_id = "sprint.migration";

global $APPLICATION;
$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);
if (!($MODULE_RIGHT >= "R")){
    $APPLICATION->AuthForm("ACCESS_DENIED");
}

CModule::IncludeModule($module_id);

$versionManager = new Sprint\Migration\VersionManager();
?>

<p>
    <a href="/bitrix/admin/sprint_migrations.php?lang=<?=LANGUAGE_ID?>"><?= GetMessage('SPRINT_MIGRATION_GOTO_MIGRATION') ?></a>
</p>


<h3><?= GetMessage('SPRINT_MIGRATION_HELP_CONFIG') ?></h3>
<div class="c-migration-block"><?php
    $configInfo = $versionManager->getConfigInfo();
    ?><?php foreach ($configInfo as $file) :?>
        <table class="c-migration-config">
            <thead>
            <tr>
                <td colspan="2">
                    <strong><?=$file['title']?></strong>
                </td>
            </tr>
            </thead>
            <tbody>
            <? foreach ($file['values'] as $key => $val) :?>
                <tr>
                    <td><?=$key?></td>
                    <td><?=$val?></td>
                </tr>
            <?endforeach;?>
            </tbody>
        </table>
    <?endforeach;?>
</div>

<h3><?= GetMessage('SPRINT_MIGRATION_HELP_DOC') ?></h3>
<div class="c-migration-block">
    <p>
        <a href="https://bitbucket.org/andrey_ryabin/sprint.migration" target="_blank">https://bitbucket.org/andrey_ryabin/sprint.migration</a>
    </p>
</div>
