<?php

namespace Sprint\Migration\Fields;

use Sprint\Migration\Interfaces\ConsoleInterface;

class CheckboxListField extends SelectField
{
    public function getDefaultValue(): array
    {
        return [];
    }

    public function makeValueFromConsole(string $rawValue): mixed
    {
        return array_filter(explode(' ', $rawValue));
    }

    public function makePlanText(mixed $value): string
    {
        if (is_array($value)) {
            return $this->getOptionNamesByIds($value);
        }
        return '';
    }

    public function readValueFromConsole(ConsoleInterface $io): string
    {
        $this->printFieldToConsole($io);

        return $io->read("Select any (separated by space): ");
    }
}