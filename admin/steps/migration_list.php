<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$listView = (
        ($_POST["step_code"] == "migration_new") ||
        ($_POST["step_code"] == "migration_list") ||
        ($_POST["step_code"] == "migration_installed")
);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $listView && check_bitrix_sessid('send_sessid')) {

    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $search = !empty($_POST['search']) ? trim($_POST['search']) : '';
    $search = Sprint\Migration\Locale::convertToUtf8IfNeed($search);

    $taskUrl = $versionManager->getConfigVal('tracker_task_url');
    $webdir = $versionManager->getConfigVal('migration_webdir');

    if ($_POST["step_code"] == "migration_new") {
        \Sprint\Migration\Module::setDbOption('admin_versions_view', 'new');
        \Sprint\Migration\Module::setDbOption('admin_versions_search', $search);
        $versions = $versionManager->getVersions(array(
            'status' => 'new',
            'search' => $search,
        ));
    }elseif ($_POST["step_code"] == "migration_installed"){
        \Sprint\Migration\Module::setDbOption('admin_versions_view', 'installed');
        \Sprint\Migration\Module::setDbOption('admin_versions_search', $search);
        $versions = $versionManager->getVersions(array(
            'status' => 'installed',
            'search' => $search,
        ));

    } else {
        \Sprint\Migration\Module::setDbOption('admin_versions_view', 'list');
        \Sprint\Migration\Module::setDbOption('admin_versions_search', $search);
        $versions = $versionManager->getVersions(array(
            'status' => '',
            'search' => $search,
        ));
    }

    ?>
    <? if (!empty($versions)):?>
        <table class="sp-list">
        <? foreach ($versions as $aItem):?>
            <tr>
                <td class="sp-list-l">
                <? if ($aItem['status'] == 'new'): ?>
                    <input disabled="disabled" onclick="migrationExecuteStep('migration_execute', {version: '<?= $aItem['version'] ?>', action: 'up'});" value="<?= GetMessage('SPRINT_MIGRATION_UP') ?>" type="button">
                <? endif ?>
                <? if ($aItem['status'] == 'installed'): ?>
                    <input disabled="disabled" onclick="migrationExecuteStep('migration_execute', {version: '<?= $aItem['version'] ?>', action: 'down'});" value="<?= GetMessage('SPRINT_MIGRATION_DOWN') ?>" type="button">
                <? endif ?>
                </td>
                <td class="sp-list-r">
                    <? if ($aItem['status'] != 'unknown' && $webdir): ?>
                        <? $href = '/bitrix/admin/fileman_file_view.php?' . http_build_query(array(
                                'lang' => LANGUAGE_ID,
                                'site' => SITE_ID,
                                'path' => $webdir . '/' . $aItem['version'] . '.php'
                            )) ?>
                        <a class="sp-item-<?= $aItem['status'] ?>" href="<?= $href ?>" target="_blank" title=""><?= $aItem['version'] ?></a>
                    <? else: ?>
                        <span class="sp-item-<?= $aItem['status'] ?>"><?= $aItem['version'] ?></span>
                    <? endif ?>
                    <?if (!empty($aItem['description'])):?><?php
                        if ($taskUrl && false !== strpos($taskUrl, '$1')){
                            $aItem['description'] = preg_replace('/#(\d+)/', '<a target="_blank" href="'.$taskUrl.'">#$1</a>', $aItem['description']);
                        }
                        ?>
                        <?= \Sprint\Migration\Out::prepareToHtml($aItem['description'])?>
                    <?endif?>
                </td>
            </tr>
        <? endforeach ?>
        </table>
    <? else: ?>
        <?= GetMessage('SPRINT_MIGRATION_LIST_EMPTY') ?>
    <? endif ?>
    <?
    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();

}