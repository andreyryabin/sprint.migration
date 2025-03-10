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
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Main'));

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

        $allFields = $helper->UserTypeEntity()->getList();
        foreach ($allFields as $index => $field) {
            $allFields[$index]['TITLE'] = $helper->UserTypeEntity()->getEntityTitle($field['ENTITY_ID']);
        }


        $entityIds = $this->addFieldAndReturn(
            'entity_id',
            [
                'title'       => Locale::getMessage('BUILDER_UserTypeEntities_EntityIds'),
                'placeholder' => '',
                'width'       => 250,
                'select'      => $this->createSelect($allFields, 'ENTITY_ID', 'TITLE'),
                'multiple'    => 1,
                'value'       => [],
            ]
        );

        $selectFields = array_filter($allFields, function ($item) use ($entityIds) {
            return in_array($item['ENTITY_ID'], $entityIds);
        });

        $items = $this->addFieldAndReturn(
            'entity_fields',
            [
                'title'       => Locale::getMessage('BUILDER_UserTypeEntities_EntityFields'),
                'placeholder' => '',
                'width'       => 250,
                'multiple'    => 1,
                'items'       => $this->createSelectWithGroups($selectFields, 'ID', 'FIELD_NAME', 'TITLE'),
                'value'       => [],
            ]
        );

        $entities = [];
        foreach ($items as $fieldId) {
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
