<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_list" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    if (\COption::GetOptionString('sprint.migration', 'admin_versions_view') != 'list'){
        \COption::SetOptionString('sprint.migration', 'admin_versions_view', 'list');
    }

    $versions = $manager->getVersions('all');

    ?>
    <?if (!empty($versions)): ?>

        <?foreach ($versions as $aItem): ?>

            <div class="c-migration-block">
                <a href="#" title="<?= GetMessage('SPRINT_MIGRATION_DESCR1') ?>" onclick="migrationMigrationDescr('<?= $aItem['version'] ?>');return false;" class="c-migration-item-<?= $aItem['type'] ?>">
                    <span><?= $aItem['version'] ?></span>
                </a>
                <?if ($aItem['type'] == 'is_new'): ?>
                    <input class="c-migration-btn" onclick="migrationExecuteStep('migration_execute', {version: '<?=$aItem['version']?>', action: 'up'});" value="<?= GetMessage('SPRINT_MIGRATION_UP') ?>" type="button">
                <?endif ?>
                <?if ($aItem['type'] == 'is_success'): ?>
                    <input class="c-migration-btn" onclick="migrationExecuteStep('migration_execute', {version: '<?=$aItem['version']?>', action: 'down'});" value="<?= GetMessage('SPRINT_MIGRATION_DOWN') ?>" type="button">
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
