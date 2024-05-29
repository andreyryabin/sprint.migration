<?php

use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\Out;
use Sprint\Migration\VersionConfig;

$request = Bitrix\Main\Context::getCurrent()->getRequest();

if ($request->isPost() && check_bitrix_sessid()) {
    if ($request->getPost("options_remove")) {
        Module::removeDbOptions();
        Out::outSuccess(
            Locale::getMessage('OPTIONS_REMOVE_success')
        );
    }

    if ($request->getPost("options_save")) {
        Module::checkDbOption('show_support', (bool)$request->getPost('show_support'));
        Module::checkDbOption('show_schemas', (bool)$request->getPost('show_schemas'));

        Out::outSuccess(
            Locale::getMessage('OPTIONS_SAVE_success')
        );
    }

    if ($request->getPost("configuration_remove")) {
        $versionConfig = new VersionConfig();
        if ($versionConfig->deleteConfig($request->getPost('configuration_name'))) {
            Out::outSuccess(
                Locale::getMessage('BUILDER_Cleaner_success')
            );
        } else {
            Out::outError(
                Locale::getMessage('BUILDER_Cleaner_error')
            );
        }
    }

    if ($request->getPost("configuration_create")) {
        $versionConfig = new VersionConfig();
        if ($versionConfig->createConfig($request->getPost('configuration_name'))) {
            Out::outSuccess(
                Locale::getMessage('BUILDER_Configurator_success')
            );
        } else {
            Out::outError(
                Locale::getMessage('BUILDER_Configurator_error')
            );
        }
    }

    if ($request->getPost("gadgets_install")) {
        /** @var $tmpmodule sprint_migration */
        $tmpmodule = CModule::CreateModuleObject(Module::ID);
        $tmpmodule->installGadgets();
        Out::outSuccess(
            Locale::getMessage('GD_INSTALL_success')
        );
    }
}
?>

<?php include __DIR__ . '/help.php' ?>
<div class="sp-separator"></div>

<div class="sp-table">
    <div class="sp-row2">
        <div class="sp-col">
            <form method="post" action="">
                <p><?= Locale::getMessage('BUILDER_CommonSettings') ?></p>
                <p>
                    <label>
                        <input <?php if (Module::isDbOptionChecked('show_schemas')){ ?>checked="checked"<?php } ?>
                               type="checkbox"
                               name="show_schemas"
                               value="1">
                        <?= Locale::getMessage('SHOW_SCHEMAS') ?>
                    </label>
                </p>
                <p>
                    <label>
                        <input <?php if (Module::isDbOptionChecked('show_support')){ ?>checked="checked"<?php } ?>
                               type="checkbox"
                               name="show_support"
                               value="1">
                        <?= Locale::getMessage('SHOW_SUPPORT') ?>
                    </label>
                </p>
                <p>
                    <input type="submit" name="options_save" value="<?= Locale::getMessage('BUILDER_SAVE') ?>">
                </p>
                <?= bitrix_sessid_post(); ?>
            </form>
        </div>
        <div class="sp-col">
            <form method="post" action="">
                <p><?= Locale::getMessage('OPTIONS_REMOVE') ?></p>
                <p><input type="submit" name="options_remove"
                          value="<?= Locale::getMessage('BUILDER_RUN') ?>"></p>
                <?= bitrix_sessid_post(); ?>
            </form>
        </div>
    </div>
</div>

<div class="sp-table">
    <div class="sp-row2">
        <div class="sp-col">
            <form method="post" action="">
                <p><?= Locale::getMessage('BUILDER_Configurator') ?></p>
                <p><input size="30" type="text" name="configuration_name" value=""
                          placeholder="<?= Locale::getMessage('BUILDER_Configurator_config_name') ?>"></p>
                <p><input type="submit" name="configuration_create"
                          value="<?= Locale::getMessage('BUILDER_CREATE') ?>"></p>
                <?= bitrix_sessid_post(); ?>
            </form>
        </div>
        <div class="sp-col">
            <form method="post" action="">
                <p><?= Locale::getMessage('BUILDER_Cleaner_desc') ?></p>
                <p><input size="30" type="text" name="configuration_name" value=""
                          placeholder="<?= Locale::getMessage('BUILDER_Cleaner_config_name') ?>"></p>
                <p><input type="submit" name="configuration_remove"
                          value="<?= Locale::getMessage('BUILDER_RUN') ?>"></p>
                <?= bitrix_sessid_post(); ?>
            </form>
        </div>
    </div>
</div>

<div class="sp-table">
    <div class="sp-row">
        <div class="sp-col">
            <form method="post" action="">
                <p><?= Locale::getMessage('GD_INSTALL') ?></p>
                <p><input type="submit" name="gadgets_install"
                          value="<?= Locale::getMessage('BUILDER_RUN') ?>"></p>
                <?= bitrix_sessid_post(); ?>
            </form>
        </div>
    </div>
</div>

<div class="sp-separator"></div>

<?php include __DIR__ . '/config_list.php' ?>
