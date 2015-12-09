<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_new" && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    \Sprint\Migration\Module::setDbOption('admin_versions_view', 'new');

    $versions = $versionManager->getVersions('up');
    ?>
    <?if (!empty($versions)): ?>

        <?foreach ($versions as $aItem): ?>
            <div class="c-migration-block">
                <a href="#" onclick="migrationMigrationInfo('<?= $aItem['version'] ?>');return false;" class="c-migration-item-is_new"><?= $aItem['version'] ?></a>
                &nbsp;
                <input onclick="migrationExecuteStep('migration_execute', {version: '<?= $aItem['version']?>', action: 'up'});" value="<?= GetMessage('SPRINT_MIGRATION_UP') ?>" type="button">
                <div id="migration_info_<?= $aItem['version'] ?>"></div>
            </div>
        <?endforeach ?>
    <?else: ?>
        <?= GetMessage('SPRINT_MIGRATION_LIST_EMPTY') ?>
    <?endif ?>
    <?
    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}