<?

use Sprint\Migration\VersionManager;
use Sprint\Migration\SchemaManager;
use Bitrix\Main\Loader;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();

if (!Loader::includeModule('sprint.migration')) {
    return false;
}
/** @var $versionConfig VersionConfig */
$versionManager = new VersionManager($versionConfig);
/** @var $versionConfig VersionConfig */
$schemaManager  = new SchemaManager($versionConfig);

$migrationPageUrl = '/bitrix/admin/sprint_migrations.php?config=cfg&lang=ru';
$schemaPageUrl    = '/bitrix/admin/sprint_migrations.php?schema=cfg&lang=ru';

$versions          = $versionManager->getVersions(['status' => 'new']);
$isAnyNewMigration = !empty($versions);

$enabledSchemas      = $schemaManager->getEnabledSchemas();
$isAnyModifiedSchema = false;

$schemaClassesToTrack = is_array($arGadgetParams['SCHEMA_CLASS_TO_TRACK_LIST']) ? array_filter($arGadgetParams['SCHEMA_CLASS_TO_TRACK_LIST']) : [];
foreach ($enabledSchemas as $schema) {
    if ($schema->isModified() && in_array(get_class($schema), $schemaClassesToTrack)) {
        $isAnyModifiedSchema = true;
        break;
    }
}

?>
<style>
    .sp-db-wrap {padding: 10px;}
    .sp-db-table {border-collapse: collapse; }
    .sp-db-table th {border-bottom: 1px solid lightgray;}
    .sp-db-table th {padding: 2px 5px; font-weight: bold;}
    .sp-db-table td {padding: 2px 5px;}
    .sp-db-table th:nth-child(1) {border-right: 1px solid lightgray;}
    .sp-db-table td:nth-child(1) {border-right: 1px solid lightgray;}
    .sp-db-table th:nth-child(2) {border-right: 1px solid lightgray;}
    .sp-db-table td:nth-child(2) {border-right: 1px solid lightgray;}

    .sp-db-label {}
    .sp-db-state {}
    .sp-db-link {text-decoration: none !important; font-size: 14pt !important;  }
    .sp-db-link:hover {text-decoration: none !important; color: blue; position: relative; top:1px;left:1px;}

    .sp-db-col-type {text-align: right;}
    .sp-db-col-value {text-align: center;}
    .sp-db-col-link {text-align: center;}

    .ball { min-height:18px; padding:0 0 0 20px; margin: 0 0 5px 0;}
    .ball_red {background:url("/bitrix/components/bitrix/desktop/templates/admin/images/lamp/red.gif") no-repeat;}
    .ball_green {background:url("/bitrix/components/bitrix/desktop/templates/admin/images/lamp/green.gif") no-repeat;}
    .ball_yellow {background:url("/bitrix/components/bitrix/desktop/templates/admin/images/lamp/yellow.gif") no-repeat;}

</style>
<div class="sp-db-wrap">
    <table class="sp-db-table">
        <thead>
            <tr>
                <th><?= GetMessage('TIP') ?></th>
                <th><?= GetMessage('SOSTOYANIE') ?></th>
                <th><?= GetMessage('PEREYTI') ?></th>
            </tr>
        </thead>
        <tr>
            <td class='sp-db-col-type'>
                <span class="sp-db-label"><?= GetMessage('MIGRATIONS') ?></span>
            </td>
            <td class='sp-db-col-value'>
                <? if ($isAnyNewMigration): ?>
                    <span class="sp-db-state ball ball_red" title='<?= GetMessage('MIGRATIONS_EST') ?>'></span>
                <? else: ?>
                    <span class="sp-db-state ball ball_green" title='<?= GetMessage('MIGRATIONS_CLEAN') ?>'></span>
                <? endif; ?>
            </td>
            <td class="sp-db-col-link">
                <a href="<?= $migrationPageUrl ?>" target='_blank' class="sp-db-link" title="<?= GetMessage('MIGRATIONS_LINK') ?>">⇒</a>
            </td>
        </tr>

        <? if ($schemaClassesToTrack): ?>
            <tr>
                <td class='sp-db-col-type'>
                    <span class="sp-db-label"><?= GetMessage('SCHEMAS') ?></span>
                </td>
                <td class='sp-db-col-value'>
                    <? if ($isAnyModifiedSchema): ?>
                        <span class="sp-db-state ball ball_red" title='<?= GetMessage('SCHEMAS_EST') ?>'></span>
                    <? else: ?>
                        <span class="sp-db-state ball ball_green" title='<?= GetMessage('SCHEMAS_CLEAN') ?>'></span>
                    <? endif; ?>
                </td>
                <td class="sp-db-col-link">
                    <a href="<?= $schemaPageUrl ?>" target='_blank' class="sp-db-link" title="<?= GetMessage('SCHEMAS_LINK') ?>">⇒</a>
                </td>
            </tr>
        <? endif; ?>

    </table>
</div>