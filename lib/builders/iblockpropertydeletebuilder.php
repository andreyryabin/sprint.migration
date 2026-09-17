<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockPropertyDeleteBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Iblock()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Iblock'));

        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_IblockPropertyDelete'),
        ]));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@temi4']),
            Locale::getMessage('DEVELOPER_URI', ['#VALUE#' => 'https://github.com/andreyryabin/sprint.migration/pull/184']),
            Locale::getMessage('BUILDER_IblockPropertyDelete_Info'),
        ]));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws MigrationException
     * @throws RebuildException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $iblocks = $helper->IblockExchange()->getIblocks();
        $iblockTypes = $helper->IblockExchange()->getIblockTypes();
        $itemsForSelect = $helper->IblockExchange()->createIblocksStructure(
            $iblockTypes,
            $iblocks
        );

        $iblockId = (int)$this->addFieldAndReturn(
            'iblock_id',
            [
                'title'       => Locale::getMessage('BUILDER_IblockExport_IblockId'),
                'placeholder' => '',
                'width'       => 250,
                'items'       => $itemsForSelect,
            ]
        );

        $properties = $this->prepareProperties($helper->Iblock()->getProperties($iblockId));

        $this->addField(
            'property_ids',
            [
                'title'    => Locale::getMessage('BUILDER_IblockPropertyDelete_Properties'),
                'width'    => 350,
                'multiple' => 1,
                'value'    => [],
                'select'   => $this->createSelect($properties, 'ID', 'PROPERTY_TITLE'),
            ]
        );

        $this->addField(
            'property_codes',
            [
                'title'  => Locale::getMessage('BUILDER_IblockPropertyDelete_Codes'),
                'width'  => 350,
                'height' => 80,
            ]
        );

        $propertyIds = $this->getFieldValue('property_ids', []);
        $propertyIds = is_array($propertyIds) ? $propertyIds : [$propertyIds];

        $propertyCodes = $this->getPropertyCodesByIds($properties, $propertyIds);
        $propertyCodes = array_merge(
            $propertyCodes,
            $this->explodePropertyCodes((string)$this->getFieldValue('property_codes'))
        );
        $propertyCodes = array_values(array_unique(array_filter($propertyCodes)));

        if (empty($propertyCodes)) {
            $this->rebuildField('property_ids');
        }

        $this->createVersionFile(
            Module::getModuleTemplateFile('IblockPropertyDelete'),
            [
                'iblock'        => $helper->Iblock()->exportIblock($iblockId),
                'propertyCodes' => $propertyCodes,
            ],
            false
        );
    }

    private function prepareProperties(array $properties): array
    {
        $properties = array_filter(
            $properties,
            fn($property) => !empty($property['CODE'])
        );

        return array_map(
            fn($property) => array_merge(
                $property,
                [
                    'PROPERTY_TITLE' => sprintf('[%s] %s', $property['CODE'], $property['NAME']),
                ]
            ),
            $properties
        );
    }

    private function getPropertyCodesByIds(array $properties, array $propertyIds): array
    {
        $propertyIds = array_map('strval', $propertyIds);

        return array_values(
            array_map(
                fn($property) => $property['CODE'],
                array_filter(
                    $properties,
                    fn($property) => in_array((string)$property['ID'], $propertyIds, true)
                )
            )
        );
    }

    private function explodePropertyCodes(string $codes): array
    {
        return array_filter(
            array_map(
                'trim',
                preg_split('/[\s,;]+/', $codes) ?: []
            )
        );
    }
}
