<? if (!$versionManager->getConfigVal('show_admin_interface')): ?>
    <div class="sp-block">
        <?= GetMessage('SPRINT_MIGRATION_ADMIN_INTERFACE_HIDDEN') ?>
    </div>
<? endif ?>

    <div class="sp-block">
        <?= GetMessage('SPRINT_MIGRATION_HELP_DOC') ?>:
        <a href="https://bitbucket.org/andrey_ryabin/sprint.migration" target="_blank">https://bitbucket.org/andrey_ryabin/sprint.migration</a>
    </div>