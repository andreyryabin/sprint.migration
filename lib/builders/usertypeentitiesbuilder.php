<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Fields\RadioListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class UserTypeEntitiesBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return true;
    }

    protected function initialize(): void
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
    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $what = $this->askField('what',
            (new RadioListField())
                ->setTitle(Locale::getMessage('BUILDER_UserTypeEntities_What'))
                ->setInPlan(false)
                ->addOption('entityId', Locale::getMessage('BUILDER_UserTypeEntities_WhatEntityId'))
                ->addOption('userTypeId', Locale::getMessage('BUILDER_UserTypeEntities_WhatUserTypeId')),
        );

        if ($what === 'userTypeId') {
            $fields = $this->askFieldsByUserTypeId();
        } else {
            $fields = $this->askFieldsByEntityId();
        }

        $fieldsIds = $this->askField('entity_fields',
            (new CheckboxListField())
                ->setTitle(Locale::getMessage('BUILDER_UserTypeEntities_EntityFields'))
                ->setOptions($fields, 'ID', 'FIELD_TITLE', 'ENTITY_TITLE'),
        );

        $entities = $helper->UserTypeEntity()->exportUserTypeEntitiesByIds($fieldsIds);

        $this->confirmVersionPlan();

        $this->createVersionFile(
            Module::getModuleTemplateFile('UserTypeEntities'),
            ['entities' => $entities],
        );
    }

    /**
     * @throws RebuildException
     * @throws HelperException
     */
    private function askFieldsByUserTypeId(): array
    {
        $userTypes = $this->getUserTypes();

        $userTypeId = $this->askField('user_type_id',
            (new RadioListField())
                ->setTitle(Locale::getMessage('BUILDER_UserTypeEntities_UserTypeId'))
                ->setInPlan(false)
                ->setOptions($userTypes, 'USER_TYPE_ID', 'TITLE'),
        );

        return $this->getUserTypeEntitiesByFilter(['USER_TYPE_ID' => $userTypeId]);
    }

    /**
     * @throws HelperException
     */
    private function getUserTypes(): array
    {
        $entityHelper = $this->getHelperManager()->UserTypeEntity();

        return array_map(
            fn($field) => array_merge($field, [
                'TITLE' => sprintf('[%s] %s', $field['USER_TYPE_ID'], $field['DESCRIPTION']),
            ]),
            $entityHelper->getUserTypes(),
        );
    }

    /**
     * @throws HelperException
     */
    private function getUserTypeEntitiesByFilter(array $filter = []): array
    {
        $entityHelper = $this->getHelperManager()->UserTypeEntity();

        return array_map(
            fn($field) => array_merge($field, [
                'ENTITY_TITLE' => $entityHelper->getEntityTitle($field['ENTITY_ID']),
                'FIELD_TITLE'  => sprintf('[%s] %s', $field['FIELD_NAME'], $field['TITLE']),
            ]),
            $entityHelper->getUserTypeEntitiesByFilter($filter),
        );
    }

    /**
     * @throws RebuildException
     * @throws HelperException
     */
    private function askFieldsByEntityId(): array
    {
        $fields = $this->getUserTypeEntitiesByFilter();

        $entityIds = $this->askField('entity_id',
            (new CheckboxListField())
                ->setTitle(Locale::getMessage('BUILDER_UserTypeEntities_EntityId'))
                ->setInPlan(false)
                ->setOptions($fields, 'ENTITY_ID', 'ENTITY_TITLE'),
        );

        return array_filter($fields, function ($item) use ($entityIds) {
            return in_array($item['ENTITY_ID'], $entityIds);
        });
    }
}
