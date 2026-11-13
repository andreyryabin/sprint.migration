<?php

namespace Sprint\Migration\Fields;

class StringField extends AbstractField
{
    private string $defaultValue = '';

    public function setSize(int $size): static
    {
        return $this->setProp('size', $size);
    }

    public function setPlaceholder(string $placeholder): static
    {
        return $this->setProp('placeholder', "$placeholder");
    }

    public function getDefaultValue(): string
    {
        return $this->defaultValue;
    }

    public function setDefaultValue(string $value): static
    {
        $this->defaultValue = $value;
        return $this;
    }
}