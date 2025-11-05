<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
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

        $userTypes = $this->getPropertyUserTypes();

        $userType = $this->addFieldAndReturn(
            'user_type',
            [
                'title'       => Locale::getMessage('BUILDER_IblockPropertyExport_UserType'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => $this->createSelect($userTypes, 'USER_TYPE', 'TITLE'),
            ]
        );

        $properties = $this->getPropertiesByFilter([
            'USER_TYPE' => $userType,
        ]);

        $propertyIds = $this->addFieldAndReturn(
            'export_props', [
                'title'    => Locale::getMessage('BUILDER_IblockPropertyExport_Properties'),
                'width'    => 250,
                'multiple' => 1,
                'value'    => [],
                'items'    => $this->createSelectWithGroups($properties, 'ID', 'PROPERTY_TITLE', 'IBLOCK_TITLE'),
            ]
        );

        $properties = array_filter(
            $properties,
            fn($property) => in_array($property['ID'], $propertyIds)
        );

        $exports = array_map(
            fn($property) => [
                'iblock' => $helper->Iblock()->exportIblock($property['IBLOCK_ID']),
                'prop'   => $helper->Iblock()->exportProperty($property['IBLOCK_ID'], ['ID' => $property['ID']]),
            ],
            $properties
        );

        $this->createVersionFile(
            Module::getModuleTemplateFile('IblockPropertyExport'),
            [
                'exports' => $exports,
            ]
        );
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
            $iblockHelper->getPropertiesByFilter($filter)
        );
    }

    private function getPropertyUserTypes(): array
    {
        $iblockHelper = $this->getHelperManager()->Iblock();

        return array_map(
            fn($field) => array_merge($field, [
                'TITLE' => sprintf('[%s] %s', $field['USER_TYPE'], $field['DESCRIPTION']),
            ]),
            $iblockHelper->getPropertyUserTypes()
        );
    }
}
