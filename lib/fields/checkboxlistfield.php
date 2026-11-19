<?php

namespace Sprint\Migration\Fields;

class CheckboxListField extends RadioListField
{
    public function getDefaultValue(): array
    {
        return [];
    }

    public function setValueFromConsole(string $rawValue): mixed
    {
        return array_filter(explode(' ', trim($rawValue)));
    }
}