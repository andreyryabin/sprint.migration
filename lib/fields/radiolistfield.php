<?php

namespace Sprint\Migration\Fields;

class RadioListField extends AbstractField
{

    public function addOption(mixed $id, string $name, string $group = '-'): static
    {
        return $this->addToProp('options', ['id' => $id, 'name' => $name, 'group' => $group]);
    }

    public function getDefaultValue(): mixed
    {
        return '';
    }

}