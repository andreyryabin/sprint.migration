<?php

namespace Sprint\Migration\Fields;

use Sprint\Migration\Exceptions\ConsoleException;
use Sprint\Migration\Interfaces\ConsoleInterface;

class SelectField extends AbstractField
{

    public function addOption(string $id, string $name, string $group = '-'): static
    {
        return $this->addToProp('options', ['id' => $id, 'name' => $name, 'group' => $group]);
    }

    public function getDefaultValue(): mixed
    {
        return '';
    }

    public function makePlanText(mixed $value): string
    {
        if (is_string($value) || is_numeric($value)) {
            $names = $this->getOptionNamesByIds([$value]);
            return $this->getTitle() . ': ' . $names[0] ?? '';
        }
        return '';
    }

    public function readValueFromConsole(ConsoleInterface $io): string
    {
        $this->printFieldToConsole($io);

        return $io->read("Select one: ");
    }

    protected function getOptionNamesByIds(array $ids): array
    {
        $options = $this->getProp('options', []);
        $names = [];
        foreach ($options as $option) {
            if (in_array($option['id'], $ids)) {
                $names[] = $option['name'];
            }
        }
        return $names;
    }

    /**
     * @throws ConsoleException
     */
    protected function printFieldToConsole(ConsoleInterface $io): void
    {
        $io->print("[pink](%s) - %s[/]", $this->getCode(), $this->getTitle());

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