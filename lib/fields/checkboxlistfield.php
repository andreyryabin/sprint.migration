<?php

namespace Sprint\Migration\Fields;

use Sprint\Migration\Interfaces\ConsoleInterface;

class CheckboxListField extends SelectField
{
    public function getDefaultValue(): array
    {
        return [];
    }

    public function checkRequiredValue(mixed $value): bool
    {
        if (!is_array($value) || empty($value)) {
            //Пустой список невалиден: отвергаем vacuous truth
            return false;
        }

        $ids = array_column($this->getProp('options', []), 'id');

        foreach ($value as $v) {
            if (!in_array($v, $ids, true)) {
                return false;
            }
        }

        return true;
    }

    public function makeValueFromConsole(string $rawValue): array
    {
        return array_filter(explode(' ', $rawValue));
    }

    public function makePlanText(mixed $value): string
    {
        if (!is_array($value)) {
            return '';
        }

        $names = $this->getOptionNamesByIds($value);
        $lines = [$this->getTitle() . ': [ul]'];
        foreach ($names as $name) {
            $lines[] = '[li]' . $name . '[/li]';
        }
        $lines[] = '[/ul]';
        return implode(PHP_EOL, $lines);
    }


    public function readValueFromConsole(ConsoleInterface $io, string $code): string
    {
        $this->printFieldToConsole($io, $code);

        return $io->read("Select any (separated by space): ");
    }
}