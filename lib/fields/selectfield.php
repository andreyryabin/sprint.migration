<?php

namespace Sprint\Migration\Fields;

use Sprint\Migration\Exceptions\ConsoleException;
use Sprint\Migration\Interfaces\ConsoleInterface;

class SelectField extends BuilderField
{

    public function addOption(string $id, string $name, string $group = '-'): static
    {
        $name = $name ?: $id;
        //$name = $name == $id ? $name : "($id) $name";

        return $this->addToProp('options', [
            'id'    => $id,
            'name'  => $name,
            'group' => $group,
        ]);
    }

    public function setOptions(array $items, string $idKey, string $nameKey, string $groupKey = '-'): static
    {
        $this->setProp('options', []);
        foreach ($items as $item) {
            if (isset($item[$idKey])) {
                $this->addOption($item[$idKey], $item[$nameKey] ?? '', $item[$groupKey] ?? '-');
            }
        }
        return $this;
    }

    public function getDefaultValue(): mixed
    {
        return '';
    }

    public function checkRequiredValue(mixed $value): bool
    {
        $ids = array_column($this->getProp('options', []), 'id');

        return in_array($value, $ids, true);
    }


    protected function makePlanForValue(mixed $value): string
    {
        if (is_string($value) || is_numeric($value)) {
            $names = $this->getOptionNamesByIds([$value]);
            return $this->getTitle() . ': ' . $names[0] ?? '';
        }
        return '';
    }

    protected function getOptionNamesByIds(array $ids): array
    {
        $options = $this->getProp('options', []);
        $names = [];
        foreach ($options as $item) {
            if (in_array($item['id'], $ids)) {
                $names[] = $item['name'];
            }
        }
        return $names;
    }

    /**
     * @throws ConsoleException
     */
    protected function printFieldToConsole(ConsoleInterface $io, string $code): void
    {
        $io->print("[pink](%s) - %s[/]", $code, $this->getTitle());

        $grouped = $this->groupOptions($this->getProp('options', []));

        foreach ($grouped as $groupName => $groupItems) {
            if ($groupName !== '-') {
                $io->print('[blue]  %s[/]', $groupName);
            }
            foreach ($groupItems as $item) {
                $io->print('    ([yellow]%s[/]) %s', $item['id'], $item['name']);
            }
        }
    }


    public function readValueFromConsole(ConsoleInterface $io, string $code): string
    {
        $this->printFieldToConsole($io, $code);

        return $io->read("Select one: ");
    }

    private function groupOptions(array $options): array
    {
        $tree = [];
        foreach ($options as $item) {
            $group = $item['group'] ?? '-';
            $tree[$group][] = $item;
        }
        return $tree;
    }
}