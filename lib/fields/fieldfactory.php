<?php

namespace Sprint\Migration\Fields;

class FieldFactory
{

    public static function createFromArray(array $field): BuilderField
    {
        if (!empty($field['height'])) {
            $obj = new TextareaField();
            $obj->setPlaceholder($field['placeholder'] ?? '');
            $obj->setRows(round($field['height'] / 10));
            if (isset($field['width'])) {
                $obj->setCols(round($field['width'] / 10));
            }
        } elseif (isset($field['select']) || isset($field['items'])) {
            $obj = $field['multiple'] ? new CheckboxListField() : new RadioListField();
            if (isset($field['select'])) {
                self::addOptionsFromSelect($obj, $field['select']);
            }
            if (isset($field['items'])) {
                self::addOptionsFromStructure($obj, $field['items']);
            }
        } else {
            $obj = new StringField();
            $obj->setDefaultValue($field['value'] ?? '');
            $obj->setPlaceholder($field['placeholder'] ?? '');
            if (isset($field['width'])) {
                $obj->setSize(round($field['width'] / 10));
            }
        }

        $obj->setTitle($field['title'] ?? '');
        $obj->setNote($field['note'] ?? '');
        $obj->setInPlan($field['in_plan'] ?? true);
        $obj->setRequired($field['required'] ?? false);

        return $obj;
    }

    private static function addOptionsFromSelect(SelectField $field, array $select): void
    {
        foreach ($select as $item) {
            $field->addOption((string) $item['value'], $item['title']);
        }
    }

    private static function addOptionsFromStructure(SelectField $field, array $items): void
    {
        foreach ($items as $group) {
            foreach ($group['items'] as $item) {
                $field->addOption((string) $item['value'], $item['title'], $group['title'] ?? '-');
            }
        }
    }
}