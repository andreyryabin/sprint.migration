<?php

namespace Sprint\Migration\Fields;

use Sprint\Migration\Exceptions\ConsoleException;
use Sprint\Migration\Output\ConsoleOutput;

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

    /**
     * @throws ConsoleException
     */
    public function getValueFromConsole(ConsoleOutput $output): mixed
    {
        $grouped = $this->groupOptions($this->getProp('options', []));

        foreach ($grouped as $groupName => $groupItems) {
            if ($groupName !== '-') {
                $output->print('---' . $groupName);
            }
            foreach ($groupItems as $item) {
                $output->print(' > ' . $item['id'] . ' (' . $item['name'] . ')');
            }
        }

        return $output->read($this->getTitle());
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