<? if (!$versionManager->getConfigVal('show_admin_interface')): ?>
    <div class="sp-block">
        <?= GetMessage('SPRINT_MIGRATION_ADMIN_INTERFACE_HIDDEN') ?>
    </div>
<? endif ?>

    <div class="sp-block">
        <?= GetMessage('SPRINT_MIGRATION_HELP_DOC') ?>:
        <a href="https://bitbucket.org/andrey_ryabin/sprint.migration" target="_blank">https://bitbucket.org/andrey_ryabin/sprint.migration</a>
    </div>

<? if ($versionManager->getConfigVal('show_other_solutions')): ?>
    <div class="sp-block">
        <img style="float: left;margin-right: 10px;" width="50" height="50"
             src="https://bitbucket.org/repo/adr668/images/1541013359-sprint-editor-icon.jpg">
        <?= GetMessage('SPRINT_MIGRATION_ABOUT_sprint_editor') ?>
        <a href="http://marketplace.1c-bitrix.ru/solutions/sprint.editor/" target="_blank">
            http://marketplace.1c-bitrix.ru/solutions/sprint.editor/
        </a>
    </div>
<? endif ?>