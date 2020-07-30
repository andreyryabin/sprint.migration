<?php

use Sprint\Migration\Locale;
use Sprint\Migration\SchemaManager;
use Sprint\Migration\VersionConfig;
use Bitrix\Main\Application;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
$request = Application::getInstance()->getContext()->getRequest();


if ($request->getPost('step_code') == "schema_list" && check_bitrix_sessid('send_sessid')) {
    /** @var $versionConfig VersionConfig */
    $schemaManager = new SchemaManager($versionConfig);

    $schemas = $schemaManager->getEnabledSchemas();

    $defaultSchemas = [];
//    foreach ($schemas as $schema) {
//        $defaultSchemas[] = $schema->getName();
//    }

    $schemaChecked = $request->getPost('schema_checked') != null ? (array)$request->getPost('schema_checked') : $defaultSchemas;

    ?>
    <table class="sp-list">
        <? foreach ($schemas as $schema): ?>
            <tr>
                <td class="sp-list-td__buttons">
                    <input data-id="<?= $schema->getName() ?>"
                           class="sp-schema adm-btn <? if (in_array($schema->getName(),
                               $schemaChecked)): ?>adm-btn-active<? endif ?>"
                           type="button"
                           value="<?= Locale::getMessage('SELECT_ONE') ?>"
                    />
                </td>
                <td class="sp-list-td__content">
                    <? $schema->outTitle(false) ?>
                    <? $schema->outDescription() ?>
                </td>
            </tr>
        <? endforeach; ?>
    </table>
    <?
}
