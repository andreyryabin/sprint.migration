<?php
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_new" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    if (\COption::GetOptionString('sprint.migration', 'admin_versions_view') != 'new'){
        \COption::SetOptionString('sprint.migration', 'admin_versions_view', 'new');
    }

    $versions = $manager->getVersions('up');

    ?>
    <?if (!empty($versions)): ?>

        <?foreach ($versions as $aItem): ?>
            <div class="c-migration-block">
                <a href="#" title="<?= GetMessage('SPRINT_MIGRATION_DESCR1') ?>" onclick="migrationMigrationDescr('<?= $aItem['version'] ?>');return false;" class="c-migration-item-is_new">
                    <span><?= $aItem['version'] ?></span>
                </a>
                <input class="c-migration-btn" onclick="migrationExecuteStep('migration_execute', {version: '<?= $aItem['version']?>', action: 'up'});" value="<?= GetMessage('SPRINT_MIGRATION_UP') ?>" type="button">
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