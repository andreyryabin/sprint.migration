<?php

namespace Sprint\Migration\Fields;

class CheckboxListField extends RadioListField
{
    public function getDefaultValue(): array
    {
        return [];
    }

}