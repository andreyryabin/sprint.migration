<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_list" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    \Sprint\Migration\Env::setDbOption('admin_versions_view', 'list');

    $versions = $versionManager->getVersions('all');

    ?>
    <?if (!empty($versions)): ?>

        <?foreach ($versions as $aItem): ?>

            <div class="c-migration-block">
                <a href="#" onclick="migrationMigrationDescr('<?= $aItem['version'] ?>');return false;" class="c-migration-item-<?= $aItem['type'] ?>"><?= $aItem['version'] ?></a>
                &nbsp;
                <?if ($aItem['type'] == 'is_new'): ?>
                    <input onclick="migrationExecuteStep('migration_execute', {version: '<?=$aItem['version']?>', action: 'up'});" value="<?= GetMessage('SPRINT_MIGRATION_UP') ?>" type="button">
                <?endif ?>
                <?if ($aItem['type'] == 'is_success'): ?>
                    <input onclick="migrationExecuteStep('migration_execute', {version: '<?=$aItem['version']?>', action: 'down'});" value="<?= GetMessage('SPRINT_MIGRATION_DOWN') ?>" type="button">
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
