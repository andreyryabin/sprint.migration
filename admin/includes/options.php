<?php

use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\Out;
use Sprint\Migration\VersionConfig;
use Bitrix\Main\Application;

$request = Application::getInstance()->getContext()->getRequest();
$requestArray = $request->getPostList()->toArray() + $request->getQueryList()->toArray();

if ($request->isPost() && check_bitrix_sessid()) {

    if (!empty($requestArray["options_remove"])) {
        Module::removeDbOptions();
        Out::outSuccess(
            Locale::getMessage('OPTIONS_REMOVE_success')
        );
    }

    if (!empty($requestArray["configuration_remove"])) {
        $versionConfig = new VersionConfig();
        if ($versionConfig->deleteConfig($requestArray['configuration_name'])) {
            Out::outSuccess(
                Locale::getMessage('BUILDER_Cleaner_success')
            );
        } else {
            Out::outError(
                Locale::getMessage('BUILDER_Cleaner_error')
            );
        }
    }

    if (!empty($requestArray["configuration_create"])) {
        $versionConfig = new VersionConfig();
        if ($versionConfig->createConfig($requestArray['configuration_name'])) {
            Out::outSuccess(
                Locale::getMessage('BUILDER_Configurator_success')
            );
        } else {
            Out::outError(
                Locale::getMessage('BUILDER_Configurator_error')
            );
        }
    }

    if (!empty($requestArray["gadgets_install"])) {
        /** @var $tmpmodule sprint_migration */
        $tmpmodule = CModule::CreateModuleObject('sprint.migration');
        $tmpmodule->installGadgets();
        Out::outSuccess(
            Locale::getMessage('GD_INSTALL_success')
        );
    }

}
?>

<? include __DIR__ . '/help.php' ?>
    <div class="sp-separator"></div>

    <div class="sp-group">
        <div class="sp-group-row2">
            <div class="sp-block">
                <form method="post" action="">
                    <p><?= Locale::getMessage('BUILDER_Configurator') ?></p>
                    <p><input size="30" type="text" name="configuration_name" value=""
                              placeholder="<?= Locale::getMessage('BUILDER_Configurator_config_name') ?>"></p>
                    <p><input type="submit" name="configuration_create"
                              value="<?= Locale::getMessage('BUILDER_NEXT') ?>"></p>
                    <?= bitrix_sessid_post(); ?>
                </form>
            </div>
            <div class="sp-block">
                <form method="post" action="">
                    <p><?= Locale::getMessage('BUILDER_Cleaner_desc') ?></p>
                    <p><input size="30" type="text" name="configuration_name" value=""
                              placeholder="<?= Locale::getMessage('BUILDER_Cleaner_config_name') ?>"></p>
                    <p><input type="submit" name="configuration_remove"
                              value="<?= Locale::getMessage('BUILDER_NEXT') ?>"></p>
                    <?= bitrix_sessid_post(); ?>
                </form>
            </div>
        </div>
    </div>

    <div class="sp-group">
        <div class="sp-group-row2">
            <div class="sp-block">
                <form method="post" action="">
                    <p><?= Locale::getMessage('GD_INSTALL') ?></p>
                    <p><input type="submit" name="gadgets_install"
                              value="<?= Locale::getMessage('BUILDER_NEXT') ?>"></p>
                    <?= bitrix_sessid_post(); ?>
                </form>
            </div>
            <div class="sp-block">
                <form method="post" action="">
                    <p><?= Locale::getMessage('OPTIONS_REMOVE') ?></p>
                    <p><input type="submit" name="options_remove"
                              value="<?= Locale::getMessage('BUILDER_NEXT') ?>"></p>
                    <?= bitrix_sessid_post(); ?>
                </form>
            </div>
        </div>
    </div>

    <div class="sp-separator"></div>

<? include __DIR__ . '/config_list.php' ?>
