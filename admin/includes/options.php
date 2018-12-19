<?php

if ($_SERVER['REQUEST_METHOD'] == "POST" && check_bitrix_sessid()) {

    if (!empty($_REQUEST["options_remove"])) {
        \Sprint\Migration\Module::removeDbOptions();
        \Sprint\Migration\Out::outSuccess(GetMessage('SPRINT_MIGRATION_OPTIONS_REMOVE_success'));
    }

    if (!empty($_REQUEST["configuration_remove"])) {
        $versionConfig = new \Sprint\Migration\VersionConfig();
        if ($versionConfig->deleteConfig($_REQUEST['configuration_name'])) {
            \Sprint\Migration\Out::outSuccess(GetMessage('SPRINT_MIGRATION_BUILDER_Cleaner_success'));
        } else {
            \Sprint\Migration\Out::outError(GetMessage('SPRINT_MIGRATION_BUILDER_Cleaner_error'));
        }
    }

    if (!empty($_REQUEST["configuration_create"])) {
        $versionConfig = new \Sprint\Migration\VersionConfig();
        if ($versionConfig->createConfig($_REQUEST['configuration_name'])) {
            \Sprint\Migration\Out::outSuccess(GetMessage('SPRINT_MIGRATION_BUILDER_Configurator_success'));
        } else {
            \Sprint\Migration\Out::outError(GetMessage('SPRINT_MIGRATION_BUILDER_Configurator_error'));
        }
    }
}
?>

<form method="post" action="">
    <p><?= GetMessage('SPRINT_MIGRATION_BUILDER_Configurator') ?></p>
    <p><input size="30" type="text" name="configuration_name" value="" placeholder="<?= GetMessage('SPRINT_MIGRATION_BUILDER_Configurator_config_name') ?>"></p>
    <p><input type="submit" name="configuration_create" value="<?= GetMessage('SPRINT_MIGRATION_BUILDER_NEXT') ?>"></p>
    <?= bitrix_sessid_post(); ?>
</form>


<br/>

<form method="post" action="">
    <p><?= GetMessage('SPRINT_MIGRATION_BUILDER_Cleaner_desc') ?></p>
    <p><input size="30" type="text" name="configuration_name" value="" placeholder="<?= GetMessage('SPRINT_MIGRATION_BUILDER_Cleaner_config_name') ?>"></p>
    <p><input type="submit" name="configuration_remove" value="<?= GetMessage('SPRINT_MIGRATION_BUILDER_NEXT') ?>"></p>
    <?= bitrix_sessid_post(); ?>
</form>

<br/>

<form method="post" action="">
    <p><?= GetMessage('SPRINT_MIGRATION_OPTIONS_REMOVE') ?></p>
    <p><input type="submit" name="options_remove" value="<?= GetMessage('SPRINT_MIGRATION_BUILDER_NEXT') ?>"></p>
    <?= bitrix_sessid_post(); ?>
</form>

<br/>

<p>
    <a href="/bitrix/admin/sprint_migrations.php?config=cfg&view=migration&lang=<?= LANGUAGE_ID ?>"><?= GetMessage('SPRINT_MIGRATION_GOTO_MIGRATION') ?></a>
</p>
<p>
    <a href="https://github.com/andreyryabin/sprint.migration" target="_blank"><?= GetMessage('SPRINT_MIGRATION_HELP_DOC') ?></a>
</p>