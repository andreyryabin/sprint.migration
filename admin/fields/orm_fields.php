<?php

use Sprint\Migration\Builder;
use Sprint\Migration\Locale;

/**
 * @var $fieldCode string
 * @var $fieldItem array
 * @var $builder   Builder
 */

$value = is_array($fieldItem['value']) ? $fieldItem['value'] : [];
$json = json_encode($value, JSON_UNESCAPED_UNICODE);
$labels = [
    'name'          => Locale::getMessage('ORM_FIELD_NAME'),
    'type'          => Locale::getMessage('ORM_FIELD_TYPE'),
    'length'        => Locale::getMessage('ORM_FIELD_LENGTH'),
    'default'       => Locale::getMessage('ORM_FIELD_DEFAULT'),
    'nullable'      => Locale::getMessage('ORM_FIELD_NULLABLE'),
    'primary'       => Locale::getMessage('ORM_FIELD_PRIMARY'),
    'autoincrement' => Locale::getMessage('ORM_FIELD_AUTOINCREMENT'),
    'delete'        => Locale::getMessage('ORM_FIELD_DELETE'),
];
$labelsJson = json_encode($labels, JSON_UNESCAPED_UNICODE);
$options = [
    'primary_enabled'       => !isset($fieldItem['primary_enabled']) || !empty($fieldItem['primary_enabled']),
    'autoincrement_enabled' => !isset($fieldItem['autoincrement_enabled']) || !empty($fieldItem['autoincrement_enabled']),
];
$optionsJson = json_encode($options, JSON_UNESCAPED_UNICODE);

?>
<div class="sp-orm-fields"
     data-field="<?= htmlspecialcharsbx($fieldCode) ?>"
     data-labels="<?= htmlspecialcharsbx($labelsJson) ?>"
     data-options="<?= htmlspecialcharsbx($optionsJson) ?>"
>
    <input type="hidden" name="<?= htmlspecialcharsbx($fieldCode) ?>" value="<?= htmlspecialcharsbx($json) ?>"/>
    <div class="sp-orm-fields-list"></div>
    <button class="adm-btn sp-orm-fields-add"><?= Locale::getMessage('ORM_FIELD_ADD') ?></button>
</div>
