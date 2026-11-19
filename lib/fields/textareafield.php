<?php

namespace Sprint\Migration\Fields;

class TextareaField extends AbstractField
{

    public function setRows(int $rowsCnt): static
    {
        return $this->setProp('rows', $rowsCnt);
    }

    public function setCols(int $colsCnt): static
    {
        return $this->setProp('cols', $colsCnt);
    }

    public function setPlaceholder(string $placeholder): static
    {
        return $this->setProp('placeholder', "$placeholder");
    }

    public function getDefaultValue(): string
    {
        return '';
    }
}