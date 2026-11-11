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

        $what = $this->addFieldAndReturn('what', [
            'title'  => Locale::getMessage('BUILDER_UserTypeEntities_What'),
            'select' => [
                [
                    'title' => Locale::getMessage('BUILDER_UserTypeEntities_WhatEntityId'),
                    'value' => 'entityId',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_UserTypeEntities_WhatUserTypeId'),
                    'value' => 'userTypeId',
                ],

            ],
        ]);

        if ($what === 'userTypeId') {
            $fields = $this->askFieldsByUserTypeId();
        } else {
            $fields = $this->askFieldsByEntityId();
        }

        $fieldsIds = $this->addFieldAndReturn('entity_fields', [
            'title'    => Locale::getMessage('BUILDER_UserTypeEntities_EntityFields'),
            'items'    => $this->createStructure($fields, 'ID', 'FIELD_TITLE', 'ENTITY_TITLE'),
            'multiple' => 1,
        ]);

        $entities = $helper->UserTypeEntity()->exportUserTypeEntitiesByIds($fieldsIds);

        $this->createVersionFile(
            Module::getModuleTemplateFile('UserTypeEntities'),
            ['entities' => $entities]
        );
    }

    /**
     * @throws RebuildException
     * @throws HelperException
     */
    private function askFieldsByEntityId(): array
    {
        $fields = $this->getUserTypeEntitiesByFilter();

        $entityIds = $this->addFieldAndReturn('entity_id', [
            'title'    => Locale::getMessage('BUILDER_UserTypeEntities_EntityId'),
            'select'   => $this->createSelect($fields, 'ENTITY_ID', 'ENTITY_TITLE'),
            'multiple' => 1,
        ]);

        return array_filter($fields, function ($item) use ($entityIds) {
            return in_array($item['ENTITY_ID'], $entityIds);
        });
    }

    /**
     * @throws RebuildException
     * @throws HelperException
     */
    private function askFieldsByUserTypeId(): array
    {
        $userTypes = $this->getUserTypes();

        $userTypeId = $this->addFieldAndReturn('user_type_id', [
            'title'  => Locale::getMessage('BUILDER_UserTypeEntities_UserTypeId'),
            'select' => $this->createSelect($userTypes, 'USER_TYPE_ID', 'TITLE'),
        ]);

        return $this->getUserTypeEntitiesByFilter(['USER_TYPE_ID' => $userTypeId]);
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
            $entityHelper->getUserTypeEntitiesByFilter($filter)
        );
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
            $entityHelper->getUserTypes()
        );
    }
}
