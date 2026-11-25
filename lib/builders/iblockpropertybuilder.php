<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockPropertyBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Iblock()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setTitle(Locale::getMessage('BUILDER_IblockPropertyExport1'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Iblock'));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws RebuildException
     * @throws MigrationException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $propertyType = $this->askField('property_type',
            (new CheckboxListField(Locale::getMessage('BUILDER_IblockPropertyExport_UserType')))
                ->setOptions($this->getPropertyTypesSelect(), 'ID', 'TITLE','GROUP'),
        );

        $properties = $this->getPropertiesByFilter(['PROPERTY_TYPE' => $propertyType]);

        $propertyIds = $this->askField('export_props',
            (new CheckboxListField(Locale::getMessage('BUILDER_IblockPropertyExport_Properties')))
                ->setOptions($properties, 'ID', 'PROPERTY_TITLE', 'IBLOCK_TITLE'),
        );

        $properties = array_filter($properties, fn($property) => in_array($property['ID'], $propertyIds));

        $exports = array_map(
            fn($property) => [
                'iblock' => $helper->Iblock()->exportIblock($property['IBLOCK_ID']),
                'prop'   => $helper->Iblock()->exportProperty($property['IBLOCK_ID'], ['ID' => $property['ID']]),
            ],
            $properties,
        );

        $this->confirmCreateVersion();

        $this->createVersionFile(
            Module::getModuleTemplateFile('IblockPropertyExport'),
            [
                'exports' => $exports,
            ],
        );
    }

    private function getPropertyTypesSelect(): array
    {
        $iblockHelper = $this->getHelperManager()->Iblock();

        return array_map(function ($item) {
            $item['TITLE'] = sprintf('[%s] %s', $item['ID'], $item['TITLE']);
            return $item;
        }, $iblockHelper->getPropertyTypes());
    }

    /**
     * @throws HelperException
     */
    private function getPropertiesByFilter(array $filter = []): array
    {
        $iblockHelper = $this->getHelperManager()->Iblock();

        return array_map(
            fn($field) => array_merge($field, [
                'IBLOCK_TITLE'   => $iblockHelper->getIblockTitle($field['IBLOCK_ID']),
                'PROPERTY_TITLE' => sprintf('[%s] %s', $field['CODE'], $field['NAME']),
            ]),
            $iblockHelper->getPropertiesByFilter($filter),
        );
    }
}
