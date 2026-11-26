<?php

namespace Sprint\Migration\Fields;

class StringField extends BuilderField
{
    public function setSize(int $size): static
    {
        return $this->setProp('size', $size);
    }

    public function setPlaceholder(string $placeholder): static
    {
        return $this->setProp('placeholder', $placeholder);
    }

    public function setDefaultValue(string $value): static
    {
        return $this->setProp('defaultValue', $value);
    }

    public function decodeValue(mixed $rawValue): string
    {
        return (string) $rawValue;
    }

    public function planValue(mixed $validValue): string
    {
        if (!$this->isPlanAllowed($validValue)) {
            return '';
        }

        return $this->getTitle() . ': ' . $validValue;
    }

    public function encodeValue(mixed $validValue): mixed
    {
        return (string) $validValue;
    }
}