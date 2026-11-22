<?php

namespace Sprint\Migration\Fields;

use Sprint\Migration\Interfaces\ConsoleInterface;

class CheckboxListField extends SelectField
{
    public function getDefaultValue(): array
    {
        return [];
    }

    public function setValueFromConsole(mixed $rawValue): mixed
    {
        return array_filter(explode(' ', $rawValue));
    }

    public function readValueFromConsole(ConsoleInterface $io): string
    {
        $this->printFieldToConsole($io);

        return $io->read("Select any (separated by space): ");
    }
}