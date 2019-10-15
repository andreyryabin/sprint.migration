<?php

use Sprint\Migration\Locale;
use Sprint\Migration\SchemaManager;
use Sprint\Migration\VersionConfig;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if ($_POST["step_code"] == "schema_list" && check_bitrix_sessid('send_sessid')) {
    /** @var $versionConfig VersionConfig */
    $schemaManager = new SchemaManager($versionConfig);

    $schemas = $schemaManager->getEnabledSchemas();

    $defaultSchemas = [];
//    foreach ($schemas as $schema) {
//        $defaultSchemas[] = $schema->getName();
//    }

    $schemaChecked = isset($_POST['schema_checked']) ? (array)$_POST['schema_checked'] : $defaultSchemas;

    ?>
    <table class="sp-list">
        <? foreach ($schemas as $schema): ?>
            <tr>
                <td class="sp-list-l" style="vertical-align: top">
                    <input data-id="<?= $schema->getName() ?>"
                           class="sp-schema adm-btn <? if (in_array($schema->getName(),
                               $schemaChecked)): ?>adm-btn-active<? endif ?>"
                           type="button"
                           value="<?= Locale::getMessage('SELECT_ONE') ?>"
                    />
                </td>
                <td class="sp-list-r">
                    <? $schema->outTitle(false) ?>
                    <? $schema->outDescription() ?>
                </td>
            </tr>
        <? endforeach; ?>
    </table>
    <?
}