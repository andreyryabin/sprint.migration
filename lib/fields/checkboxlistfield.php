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
            $names = $this->getOptionNamesByIds($value);
            $lines = [$this->getTitle() . ': [ul]'];
            foreach ($names as $name) {
                $lines[] = '[li]' . $name . '[/li]';
            }
            $lines[] = '[/ul]';
            return implode(PHP_EOL, $lines);
        }
        return '';
    }

    public function readValueFromConsole(ConsoleInterface $io, string $code): string
    {
        $this->printFieldToConsole($io, $code);

        return $io->read("Select any (separated by space): ");
    }
}