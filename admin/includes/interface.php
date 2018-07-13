<input type="hidden" value="<?= bitrix_sessid() ?>" name="send_sessid"/>
<div id="migration-container">
    <? $tabControl1 = new CAdminTabControl("tabControl2", array(
        array(
            "DIV" => "tab1",
            "TAB" => GetMessage('SPRINT_MIGRATION_TAB1'),
            "TITLE" => GetMessage('SPRINT_MIGRATION_TAB1_TITLE')
        ),
        array(
            "DIV" => "tab3",
            "TAB" => GetMessage('SPRINT_MIGRATION_TAB3'),
            "TITLE" => GetMessage('SPRINT_MIGRATION_TAB3_TITLE')
        ),
    ));

    $tabControl1->Begin();
    $tabControl1->BeginNextTab();
    ?>
    <tr>
        <td style="vertical-align: top;">
            <div id="migration_migrations"></div>
        </td>
    </tr>
    <? $tabControl1->BeginNextTab(); ?>
    <tr>
        <td style="vertical-align: top;">
            <div id="migration_progress" style="overflow-x:auto;overflow-y: scroll;max-height: 320px;"></div>
        </td>
    </tr>
    <? $tabControl1->Buttons(); ?>
    <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_UP_START') ?>"
           onclick="migrationMigrationsUpConfirm();" class="adm-btn-green"/>
    <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_DOWN_START') ?>"
           onclick="migrationMigrationsDownConfirm();"/>
    <div class="sp-filter">
        <? $search = \Sprint\Migration\Module::getDbOption('admin_versions_search', ''); ?>
        <input placeholder="<?= GetMessage('SPRINT_MIGRATION_SEARCH') ?>" style="" type="text"
               value="<?= $search ?>" class="adm-input" name="migration_search"/>
        <? $view = \Sprint\Migration\Module::getDbOption('admin_versions_view', 'list'); ?>
        <select class="sp-stat">
            <option <? if ($view == 'list'): ?>selected="selected"<? endif ?>
                    value="list"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_LIST') ?></option>
            <option <? if ($view == 'new'): ?>selected="selected"<? endif ?>
                    value="new"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_NEW') ?></option>
            <option <? if ($view == 'installed'): ?>selected="selected"<? endif ?>
                    value="installed"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_INSTALLED') ?></option>
            <option <? if ($view == 'status'): ?>selected="selected"<? endif ?>
                    value="status"><?= GetMessage('SPRINT_MIGRATION_TOGGLE_STATUS') ?></option>
        </select>
        <input type="button" value="<?= GetMessage('SPRINT_MIGRATION_SEARCH') ?>" class="sp-search"/>
    </div>
    <? $tabControl1->End(); ?>

    <div style="margin-top: 10px">&nbsp;</div>

    <? $builders = $versionManager->getVersionBuilders();?>
    <? foreach ($builders as $builderName => $builderClass): ?>
        <div class="sp-block">
            <? $builder = $versionManager->createVersionBuilder($builderName) ?>
            <div class="sp-block_title"><?= $builder->getTitle() ?></div>
            <div class="sp-block_body sp-builder-form"><?=$builder->render()?></div>
        </div>
    <? endforeach; ?>

    <div style="margin-top: 10px">&nbsp;</div>

    <div class="sp-block">
        <div class="sp-block_title"><?= GetMessage('SPRINT_MIGRATION_MARK') ?></div>
        <div class="sp-block_body"><? include __DIR__ . '/mark_form.php' ?></div>
    </div>

    <div class="sp-block">
        <div class="sp-block_title"><?= GetMessage('SPRINT_MIGRATION_CONFIG_LIST') ?></div>
        <div class="sp-block_body"><? include __DIR__ . '/config_list.php' ?></div>
    </div>

    <div style="margin-top: 10px">&nbsp;</div>

</div>
