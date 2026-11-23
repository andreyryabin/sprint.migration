<?php

namespace Sprint\Migration\Traits;

use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Fields\FieldFactory;
use Sprint\Migration\Fields\RadioListField;

trait LegacyBuilderTrait
{
    /**
     * @throws RebuildException
     * @deprecated, меняем на askField(string $code, Field $field)
     */
    protected function addFieldAndReturn(string $code, array $field)
    {
        return $this->askField($code, FieldFactory::createFromArray($field));
    }

    /**
     * @deprecated, меняем на showField(string $code, Field $field)
     */
    protected function addField(string $code, array $field): void
    {
        $this->showField($code, FieldFactory::createFromArray($field));
    }

    /**
     * @throws RebuildException
     * @deprecated, меняем на ResetField(string $code)
     */
    protected function rebuildField(string $code): void
    {
        $this->resetField($code);
    }

    /**
     * @deprecated, меняем на:
     * (new CheckboxListField())->setOptions($items, $idKey, $titleKey, $groupKey)
     * (new RadioListField())->setOptions($items, $idKey, $titleKey, $groupKey)
     */
    protected function createSelect(
        array $items,
        string $idKey,
        string $titleKey,
        bool $withIdInTitle = false,
    ): array {
        $select = [];
        foreach ($items as $item) {
            $value = $item[$idKey];
            $title = $withIdInTitle ? "[$value] $item[$titleKey]" : $item[$titleKey];

            $select[$value] = [
                'title' => $title,
                'value' => $value,
            ];
        }
        return $select;
    }

    /**
     * @deprecated, меняем на:
     *  (new CheckboxListField())->setOptions($items, $idKey, $titleKey, $groupKey)
     *  (new RadioListField())->setOptions($items, $idKey, $titleKey, $groupKey)
     */
    protected function createStructure(
        array $items,
        string $idKey,
        string $titleKey,
        string $groupKey = '-',
    ): array {
        $select = [];
        foreach ($items as $item) {
            $groupId = $item[$groupKey] ?? 'Group';

            $value = $item[$idKey];
            $title = $item[$titleKey];

            $select[$groupId] ??= [
                'title' => $groupId,
                'items' => [],
            ];

            $select[$groupId]['items'][] = [
                'title' => $title,
                'value' => $value,
            ];
        }

        return $select;
    }
}