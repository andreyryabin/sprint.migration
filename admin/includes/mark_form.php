<div id="migration_migration_mark_result"></div>
<p>
    <input placeholder="<?= GetMessage('SPRINT_MIGRATION_MARK_VERSION') ?>|installed|new|unknown" type="text"
           style="width: 250px;" id="migration_migration_mark" value=""/>
</p>
<p>
    <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_MARK_AS_INSTALLED') ?>"
           onclick="migrationMarkMigration('installed');"/>
</p>
<p>
    <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_MARK_AS_NEW') ?>"
           onclick="migrationMarkMigration('new');"/>
</p>