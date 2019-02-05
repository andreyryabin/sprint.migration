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
                           class="sp-schema adm-btn <? if (in_array($schema->getName(), $schemaChecked)): ?>adm-btn-active<? endif ?>"
                           type="button"
                           value="Выбрать"
                    />
                </td>
                <td class="sp-list-r">
                    <? $schema->outTitle(false)?>
                    <? $schema->outDescription() ?>
                </td>
            </tr>
        <? endforeach; ?>
    </table>
    <?
    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}