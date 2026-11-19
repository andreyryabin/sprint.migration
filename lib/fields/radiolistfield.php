<?php

namespace Sprint\Migration\Fields;

use Sprint\Migration\Interfaces\ConsoleInterface;

class RadioListField extends AbstractField
{

    public function addOption(mixed $id, string $name, string $group = '-'): static
    {
        return $this->addToProp('options', ['id' => $id, 'name' => $name, 'group' => $group]);
    }

    public function getDefaultValue(): mixed
    {
        return '';
    }

    public function readValueFromConsole(ConsoleInterface $io): string
    {
        $grouped = $this->groupOptions($this->getProp('options', []));

        foreach ($grouped as $groupName => $groupItems) {
            if ($groupName !== '-') {
                $io->print('---' . $groupName);
            }
            foreach ($groupItems as $item) {
                $io->print(' > ' . $item['id'] . ' (' . $item['name'] . ')');
            }
        }

        return parent::readValueFromConsole($io);
    }


    private function groupOptions(array $options): array
    {
        $tree = [];
        foreach ($options as $item) {
            $groupName = $item['group'] ?? '-';
            if (!isset($tree[$groupName])) {
                $tree[$groupName] = [];
            }
            $tree[$groupName][] = $item;
        }
        return $tree;
    }
}