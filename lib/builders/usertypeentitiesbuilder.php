<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class UserTypeEntitiesBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_UserTypeEntities1'));
        $this->setGroup('Main');

        $this->addVersionFields();
    }

    /**
     * @throws RebuildException
     * @throws HelperException
     * @throws MigrationException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $allFields = $this->getHelperManager()->UserTypeEntity()->getList();

        $selectEntities = [];
        foreach ($allFields as $entityField) {
            $selectEntities[$entityField['ENTITY_ID']] = [
                'ID' => $entityField['ENTITY_ID'],
            ];
        }

        $entityId = $this->addFieldAndReturn(
            'entity_id',
            [
                'title'       => Locale::getMessage('BUILDER_UserTypeEntities_EntityId'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => $this->createSelect($selectEntities, 'ID', 'ID'),
                'value'       => '',
            ]
        );

        $selectFields = array_filter($allFields, function ($entityField) use ($entityId) {
            return $entityField['ENTITY_ID'] == $entityId;
        });

        $entityFields = $this->addFieldAndReturn(
            'entity_fields',
            [
                'title'       => Locale::getMessage('BUILDER_UserTypeEntities_EntityFields'),
                'placeholder' => '',
                'width'       => 250,
                'multiple'    => 1,
                'select'      => $this->createSelect($selectFields, 'ID', 'FIELD_NAME'),
                'value'       => [],
            ]
        );

        $entities = [];
        foreach ($entityFields as $fieldId) {
            $entity = $helper->UserTypeEntity()->exportUserTypeEntity($fieldId);
            if (!empty($entity)) {
                $entities[] = $entity;
            }
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/UserTypeEntities.php',
            [
                'entities' => $entities,
            ]
        );
    }
}
