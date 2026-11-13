<?php

namespace Sprint\Migration\Fields;
class FieldFactory
{

    public static function createFromArray(string $code, array $field): AbstractField
    {
        if (!empty($field['height'])) {
            $obj = new TextareaField($code);
            $obj->setPlaceholder($field['placeholder'] ?? '');
            $obj->setRows(round($field['height'] / 10));
            if (isset($field['width'])) {
                $obj->setCols(round($field['width'] / 10));
            }
        } elseif (isset($field['select']) || isset($field['items'])) {
            $obj = $field['multiple'] ? new CheckboxListField($code) : new RadioListField($code);
            if (isset($field['select'])) {
                self::addOptionsFromSelect($obj, $field['select']);
            }
            if (isset($field['items'])) {
                self::addOptionsFromStructure($obj, $field['items']);
            }
        } else {
            $obj = new StringField($code);
            $obj->setPlaceholder($field['placeholder'] ?? '');
            if (isset($field['width'])) {
                $obj->setSize(round($field['width'] / 10));
            }
        }

        $obj->setTitle($field['title'] ?? '');
        $obj->setNote($field['note'] ?? '');

        return $obj;
    }

    private static function addOptionsFromSelect(RadioListField $field, array $select): void
    {
        foreach ($select as $item) {
            $field->addOption($item['value'], $item['title']);
        }
    }

    private static function addOptionsFromStructure(RadioListField $field, array $items): void
    {
        foreach ($items as $group) {
            foreach ($group['items'] as $item) {
                $field->addOption($item['value'], $item['title'], $group['title'] ?? '-');
            }
        }
    }


}