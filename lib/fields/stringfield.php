<?php

namespace Sprint\Migration\Fields;

class StringField extends AbstractField
{

    public function setSize(int $size): static
    {
        return $this->setProp('size', $size);
    }

    public function setPlaceholder(string $placeholder): static
    {
        return $this->setProp('placeholder', "$placeholder");
    }

}