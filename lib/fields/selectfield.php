<?php

namespace Sprint\Migration\Fields;

class SelectField extends BuilderField
{
    public function createValidValue(mixed $rawValue): mixed
    {
        $value =  trim((string) $rawValue);

        $ids = $this->getOptionIds();

        return in_array($value, $ids, true) ? $value : '';
    }

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


    public function createPlanForValue(mixed $validValue): string
    {
        if (!$this->isPlanAllowed($validValue)) {
            return '';
        }
        $names = $this->getOptionNamesByIds([$validValue]);

        return $this->getTitle() . ': ' . $names[0] ?? '';
    }

    public function getOptions(): array
    {
        $opt = $this->getProp('options', []);

        return is_array($opt) ? $opt : [];
    }

    protected function getOptionIds(): array
    {
        return array_column($this->getOptions(), 'id');
    }

    protected function getOptionNamesByIds(array $ids): array
    {
        $filtered = array_filter($this->getOptions(), fn($item) => in_array($item['id'], $ids));

        return array_column($filtered, 'name');
    }

}