<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$hasSteps = (
($_POST["step_code"] == "schema_list")
);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $hasSteps && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    /** @var $versionConfig \Sprint\Migration\VersionConfig */
    $schemaManager = new \Sprint\Migration\SchemaManager($versionConfig);

    $schemas = $schemaManager->getEnabledSchemas();

    $defaultSchemas = array();
    foreach ($schemas as $schema) {
        $defaultSchemas[] = $schema->getName();
    }

    $schemaChecked = isset($_POST['schema_checked']) ? (array)$_POST['schema_checked'] : $defaultSchemas;

    ?>
    <? foreach ($schemas as $schema): ?>
        <label>
            <input <? if (in_array($schema->getName(), $schemaChecked)): ?>checked="checked"<? endif ?>
                   id="sp-schema<?= $schema->getName() ?>"
                   value="<?= $schema->getName() ?>"
                   class="sp-schema"
                   type="checkbox"/>
            <?= \Sprint\Migration\Out::prepareToHtml('[blue]' . $schema->getTitle() . '[/]') ?>
            <br/>
            <? $schema->outDescription() ?>
        </label>
    <? endforeach; ?>
    <?
    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}