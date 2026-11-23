<?php

namespace Sprint\Migration\Builders;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Fields\RadioListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class OrderPropertiesBuilder extends VersionBuilder
{
    const UPDATE_METHOD_NOT = 'not';
    const UPDATE_METHOD_CODE = 'code';
    const UPDATE_METHOD_XML_ID = 'xml_id';

    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->OrderProperties()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setTitle(implode(' ', [
            Locale::getMessage('BUILDER_OrderProperties'),
        ]));

        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Sale'));

        $this->setDescription(implode(PHP_EOL, [
            Locale::getMessage('DEVELOPER_NAME', ['#VALUE#' => '@ashirchkov']),
            Locale::getMessage(
                'DEVELOPER_URI',
                ['#VALUE#' => 'https://github.com/andreyryabin/sprint.migration/pull/170'],
            ),
        ]));

        $this->addVersionFields();
    }

    /**
     * @throws MigrationException
     * @throws ObjectPropertyException
     * @throws ArgumentException
     * @throws RebuildException
     * @throws SystemException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $personTypeId = $this->askField('person_type',
            (new RadioListField())
                ->setTitle(Locale::getMessage('BUILDER_OrderProperties_PersonType'))
                ->setOptions($helper->OrderProperties()->getPersonTypes(), 'ID', 'NAME'),
        );

        if (empty($personTypeId)) {
            return;
        }

        $properties = $helper->OrderProperties()->getOrderPropertiesByPersonType($personTypeId);

        $propertyIds = $this->askField('properties',
            (new CheckboxListField())
                ->setTitle(Locale::getMessage('BUILDER_OrderProperties_Properties'))
                ->setOptions($this->getPropertiesSelect($personTypeId), 'ID', 'NAME'),
        );

        $updateMethod = $this->askField('update_method',
            (new RadioListField())
                ->setTitle(Locale::getMessage('BUILDER_OrderProperties_UpdateMethod'))
                ->addOption(self::UPDATE_METHOD_NOT, Locale::getMessage('BUILDER_OrderProperties_NotUpdate'))
                ->addOption(self::UPDATE_METHOD_CODE, Locale::getMessage('BUILDER_OrderProperties_SavePropertyByCode'))
                ->addOption(self::UPDATE_METHOD_XML_ID,
                    Locale::getMessage('BUILDER_OrderProperties_SavePropertyByXmlId')),
        );

        $propertyVariants = [];
        $migratePropertyVariants = $this->askField('migrate_property_variants',
            (new RadioListField())
                ->setTitle(Locale::getMessage('BUILDER_OrderProperties_MigratePropertyVariants'))
                ->addOption('N', Locale::getMessage('BUILDER_OrderProperties_MigratePropertyVariants_No'))
                ->addOption('Y', Locale::getMessage('BUILDER_OrderProperties_MigratePropertyVariants_Yes')),
        );
        if ($migratePropertyVariants === 'Y') {
            $propertyVariants = $helper->OrderProperties()->getOrderPropertyVariants($propertyIds);
        }

        $this->confirmVersionPlan();

        $this->createVersionFile(
            Module::getModuleTemplateFile('OrderPropertiesExport'),
            [
                'updateMethod'     => $updateMethod,
                'properties'       => array_filter(
                    $properties,
                    fn($property) => in_array($property['ID'], $propertyIds),
                ),
                'propertyVariants' => $propertyVariants,
            ],
        );
    }

    /**
     * @throws SystemException
     * @throws ArgumentException
     */
    protected function getPropertiesSelect(string $personTypeId): array
    {
        $helper = $this->getHelperManager();

        return array_map(
            fn($property) => array_merge(
                $property,
                ['NAME' => sprintf('[%s] %s', $property['CODE'] ?: $property['ID'], $property['NAME'])],
            ),
            $helper->OrderProperties()->getOrderPropertiesByPersonType($personTypeId),
        );
    }
}
