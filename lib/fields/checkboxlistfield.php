<?php

namespace Sprint\Migration\Fields;

use Sprint\Migration\Helpers\TextHelper;

class CheckboxListField extends SelectField
{
    public function decodeValue(mixed $rawValue): mixed
    {
        if (is_string($rawValue)) {
            //из консоли приходит строка разделённая пробелами
            //нужно уметь с ней работать
            $value = (new TextHelper())->explodeValues($rawValue);
        } else {
            $value = is_array($rawValue) ? $rawValue : [];
        }

        $ids = $this->getOptionIds();

        //пропускаем если все значения валидные
        foreach ($value as $v) {
            if (!in_array($v, $ids, true)) {
                return [];
            }
        }

        return $value;
    }

    public function planValue(mixed $validValue): string
    {
        if (!$this->isPlanAllowed($validValue)) {
            return '';
        }

        $names = $this->getOptionNamesByIds($validValue);

        $lines = [$this->getTitle() . ':'];
        foreach ($names as $name) {
            $lines[] = '[-]' . $name . '[/]';
        }
        return implode(PHP_EOL, $lines);
    }
}