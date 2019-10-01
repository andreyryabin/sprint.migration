<?php

use Sprint\Migration\Module;
use Sprint\Migration\Out;
use Sprint\Migration\VersionConfig;

if ($_SERVER['REQUEST_METHOD'] == "POST" && check_bitrix_sessid()) {

    if (!empty($_REQUEST["options_remove"])) {
        Module::removeDbOptions();
        Out::outSuccess(GetMessage('SPRINT_MIGRATION_OPTIONS_REMOVE_success'));
    }

    if (!empty($_REQUEST["configuration_remove"])) {
        $versionConfig = new VersionConfig();
        if ($versionConfig->deleteConfig($_REQUEST['configuration_name'])) {
            Out::outSuccess(GetMessage('SPRINT_MIGRATION_BUILDER_Cleaner_success'));
        } else {
            Out::outError(GetMessage('SPRINT_MIGRATION_BUILDER_Cleaner_error'));
        }
    }

    if (!empty($_REQUEST["configuration_create"])) {
        $versionConfig = new VersionConfig();
        if ($versionConfig->createConfig($_REQUEST['configuration_name'])) {
            Out::outSuccess(GetMessage('SPRINT_MIGRATION_BUILDER_Configurator_success'));
        } else {
            Out::outError(GetMessage('SPRINT_MIGRATION_BUILDER_Configurator_error'));
        }
    }

    if (!empty($_REQUEST["gadgets_install"])){
        /** @var $tmpmodule \sprint_migration */
        $tmpmodule = \CModule::CreateModuleObject('sprint.migration');
        $tmpmodule->installGadgets();
        Out::outSuccess(GetMessage('SPRINT_MIGRATION_GADGETS_INSTALL_success'));
    }

}
?>

<? include __DIR__ . '/help.php' ?>
    <div class="sp-separator"></div>

    <div class="sp-group">
        <div class="sp-group-row2">
            <div class="sp-block">
                <form method="post" action="">
                    <p><?= GetMessage('SPRINT_MIGRATION_BUILDER_Configurator') ?></p>
                    <p><input size="30" type="text" name="configuration_name" value=""
                              placeholder="<?= GetMessage('SPRINT_MIGRATION_BUILDER_Configurator_config_name') ?>"></p>
                    <p><input type="submit" name="configuration_create"
                              value="<?= GetMessage('SPRINT_MIGRATION_BUILDER_NEXT') ?>"></p>
                    <?= bitrix_sessid_post(); ?>
                </form>
            </div>
            <div class="sp-block">
                <form method="post" action="">
                    <p><?= GetMessage('SPRINT_MIGRATION_BUILDER_Cleaner_desc') ?></p>
                    <p><input size="30" type="text" name="configuration_name" value=""
                              placeholder="<?= GetMessage('SPRINT_MIGRATION_BUILDER_Cleaner_config_name') ?>"></p>
                    <p><input type="submit" name="configuration_remove"
                              value="<?= GetMessage('SPRINT_MIGRATION_BUILDER_NEXT') ?>"></p>
                    <?= bitrix_sessid_post(); ?>
                </form>
            </div>
        </div>
    </div>

    <div class="sp-group">
        <div class="sp-group-row2">
            <div class="sp-block">
                <form method="post" action="">
                    <p><?= GetMessage('SPRINT_MIGRATION_GADGETS_INSTALL') ?></p>
                    <p><input type="submit" name="gadgets_install"
                              value="<?= GetMessage('SPRINT_MIGRATION_BUILDER_NEXT') ?>"></p>
                    <?= bitrix_sessid_post(); ?>
                </form>
            </div>
            <div class="sp-block">
                <form method="post" action="">
                    <p><?= GetMessage('SPRINT_MIGRATION_OPTIONS_REMOVE') ?></p>
                    <p><input type="submit" name="options_remove"
                              value="<?= GetMessage('SPRINT_MIGRATION_BUILDER_NEXT') ?>"></p>
                    <?= bitrix_sessid_post(); ?>
                </form>
            </div>
        </div>
    </div>

    <div class="sp-separator"></div>

<? include __DIR__ . '/config_list.php' ?>