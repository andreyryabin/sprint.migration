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

        $typeCodes = $this->addFieldAndReturn(
            'type_codes',
            [
                'title'       => Locale::getMessage('BUILDER_UserTypeEntities_EntityId'),
                'placeholder' => '',
                'width'       => 250,
                'multiple'    => 1,
                'items'       => $this->getEntitiesSelect(),
                'value'       => [],
            ]
        );

        $entities = [];
        foreach ($typeCodes as $fieldId) {
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

    protected function getEntitiesSelect(): array
    {
        return $this->createSelectWithGroups(
            $this->getHelperManager()->UserTypeEntity()->getList(),
            'ENTITY_ID',
            'ID',
            'FIELD_NAME'
        );
    }
}
