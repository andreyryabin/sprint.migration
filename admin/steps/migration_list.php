<?php

$listView = (($_POST["step_code"] == "migration_new") || ($_POST["step_code"] == "migration_list"));

if ($_SERVER["REQUEST_METHOD"] == "POST" && $listView && check_bitrix_sessid('send_sessid')) {

    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $webdir = \Sprint\Migration\Module::getMigrationWebDir();
    if ($_POST["step_code"] == "migration_new"){
        \Sprint\Migration\Module::setDbOption('admin_versions_view', 'new');
        $versions = $versionManager->getVersions('up');
    } else {
        \Sprint\Migration\Module::setDbOption('admin_versions_view', 'list');
        $versions = $versionManager->getVersions('all');
    }

    ?>
    <? if (!empty($versions)): ?>
        <table style="border-collapse: collapse;width: 100%">
        <? foreach ($versions as $aItem):?>
            <tr>
                <td style="text-align: left;width: 50%;padding: 5px;">
                <? if ($aItem['type'] != 'is_unknown' && $webdir): ?>
                    <? $href = '/bitrix/admin/fileman_file_view.php?' . http_build_query(array(
                            'lang' => LANGUAGE_ID,
                            'site' => SITE_ID,
                            'path' => $webdir . '/' . $aItem['version'] . '.php'
                        )) ?>
                    <a class="c-migration-item-<?= $aItem['type'] ?>" href="<?= $href ?>" target="_blank" title=""><?= $aItem['version'] ?></a>
                <? else: ?>
                    <span class="c-migration-item-<?= $aItem['type'] ?>"><?= $aItem['version'] ?></span>
                <? endif ?>
                    <?if (!empty($aItem['description'])):?><?=$aItem['description']?><?endif?>
                </td>
                <td style="text-align: left;width: 50%;padding: 5px;vertical-align: top">
                <? if ($aItem['type'] == 'is_new'): ?>
                    <input disabled="disabled" onclick="migrationExecuteStep('migration_execute', {version: '<?= $aItem['version'] ?>', action: 'up'});" value="<?= GetMessage('SPRINT_MIGRATION_UP') ?>" type="button">
                <? endif ?>
                <? if ($aItem['type'] == 'is_installed'): ?>
                    <input disabled="disabled" onclick="migrationExecuteStep('migration_execute', {version: '<?= $aItem['version'] ?>', action: 'down'});" value="<?= GetMessage('SPRINT_MIGRATION_DOWN') ?>" type="button">
                <? endif ?>
                </td>
            </tr>
        <? endforeach ?>
        </table>
    <? else: ?>
        <p style="text-align: center"><?= GetMessage('SPRINT_MIGRATION_LIST_EMPTY') ?></p>
    <? endif ?>
    <?
    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();

}