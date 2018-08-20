<? if (!$versionManager->getConfigVal('show_admin_interface')): ?>
    <div class="sp-block">
        <?= GetMessage('SPRINT_MIGRATION_ADMIN_INTERFACE_HIDDEN') ?>
    </div>
<? endif ?>

<div class="sp-block">
    <?= GetMessage('SPRINT_MIGRATION_HELP_DOC') ?>:
    <a href="https://github.com/andreyryabin/sprint.migration" target="_blank">https://github.com/andreyryabin/sprint.migration</a>
</div>