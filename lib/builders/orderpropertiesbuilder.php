<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Bitrix\Main\SystemException;
use Bitrix\Main\ArgumentException;
use Sprint\Migration\VersionBuilder;

class OrderPropertiesBuilder extends VersionBuilder
{
    const UPDATE_METHOD_NOT    = 'not';
    const UPDATE_METHOD_CODE   = 'code';
    const UPDATE_METHOD_XML_ID = 'xml_id';

    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->OrderProperties()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_OrderProperties'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Sale'));
        $this->setDescription("Форму разработал @ashirchkov\nhttps://github.com/andreyryabin/sprint.migration/pull/170");

        $this->addVersionFields();
    }

    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $personTypeId = $this->addFieldAndReturn(
            'person_type',
            [
                'title'  => Locale::getMessage('BUILDER_OrderProperties_PersonType'),
                'width'  => 250,
                'value'  => '',
                'select' => array_map(fn($personType) => [
                    'title' => $personType['NAME'],
                    'value' => $personType['ID'],
                ], $helper->OrderProperties()->getPersonTypes()),
            ]
        );

        if(empty($personTypeId)) {
            return;
        }

        $properties = $helper->OrderProperties()->getOrderPropertiesByPersonType($personTypeId);

        $propertyIds = $this->addFieldAndReturn(
            'properties',
            [
                'title'       => Locale::getMessage('BUILDER_OrderProperties_Properties'),
                'placeholder' => '',
                'width'       => 250,
                'multiple'    => 1,
                'select'       => $this->getPropertiesSelect($personTypeId),
                'value'       => [],
            ]
        );

        $updateMethod = $this->addFieldAndReturn(
            'update_method', [
                'title'       => Locale::getMessage('BUILDER_OrderProperties_UpdateMethod'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => [
                    [
                        'title' => Locale::getMessage('BUILDER_OrderProperties_NotUpdate'),
                        'value' => self::UPDATE_METHOD_NOT,
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_OrderProperties_SavePropertyByCode'),
                        'value' => self::UPDATE_METHOD_CODE,
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_OrderProperties_SavePropertyByXmlId'),
                        'value' => self::UPDATE_METHOD_XML_ID,
                    ],
                ],
            ]
        );

        $propertyVariants = [];
        $migratePropertyVariants = $this->addFieldAndReturn(
            'migrate_property_variants', [
                'title'       => Locale::getMessage('BUILDER_OrderProperties_MigratePropertyVariants'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => [
                    [
                        'title' => Locale::getMessage('BUILDER_OrderProperties_MigratePropertyVariants_No'),
                        'value' => 0,
                    ],
                    [
                        'title' => Locale::getMessage('BUILDER_OrderProperties_MigratePropertyVariants_Yes'),
                        'value' => 1,
                    ],
                ],
            ]
        );
        if((int) $migratePropertyVariants > 0) {
            $propertyVariants = $helper->OrderProperties()->getOrderPropertyVariants($propertyIds);
        }

        $this->createVersionFile(
            Module::getModuleTemplateFile('OrderPropertiesExport'),
            [
                'updateMethod' => $updateMethod,
                'properties' => array_filter(
                    $properties,
                    fn($property) => in_array($property['ID'], $propertyIds)
                ),
                'propertyVariants' => $propertyVariants,
            ]
        );
    }

    /**
     * @throws SystemException
     * @throws ArgumentException
     */
    protected function getPropertiesSelect(string $personTypeId): array
    {
        $helper = $this->getHelperManager();

        $properties = array_map(
            fn($property) => array_merge(
                $property,
                ['NAME' => sprintf('[%s] %s', $property['CODE'] ? : $property['ID'], $property['NAME'])]
            ),
            $helper->OrderProperties()->getOrderPropertiesByPersonType($personTypeId)
        );

        return $this->createSelect($properties, 'ID', 'NAME');
    }
}
