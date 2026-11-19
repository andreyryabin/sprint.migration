<?php

namespace Sprint\Migration\Fields;

use Sprint\Migration\Exceptions\ConsoleException;
use Sprint\Migration\Output\ConsoleOutput;

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
    /**
     * @throws ConsoleException
     */
    public function getValueFromConsole(ConsoleOutput $output): string
    {
        return $output->read($this->getTitle());
    }
}