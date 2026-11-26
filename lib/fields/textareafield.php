<?php

namespace Sprint\Migration\Fields;

class TextareaField extends StringField
{

    public function setRows(int $rowsCnt): static
    {
        return $this->setProp('rows', $rowsCnt);
    }

    public function setCols(int $colsCnt): static
    {
        return $this->setProp('cols', $colsCnt);
    }

    public function setSize(int $size): static
    {
        return $this->setCols($size);
    }
}