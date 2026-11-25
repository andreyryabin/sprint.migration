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
            (new RadioListField(Locale::getMessage('BUILDER_UserTypeEntities_What')))
                ->setInPlan(false)
                ->addOption('entityId', Locale::getMessage('BUILDER_UserTypeEntities_WhatEntityId'))
                ->addOption('userTypeId', Locale::getMessage('BUILDER_UserTypeEntities_WhatUserTypeId')),
        );

        if ($what === 'entityId') {
            $fields = $this->askFieldsByEntityId();
        } else {
            $fields = $this->askFieldsByUserTypeId();
        }

        $fieldsIds = $this->askField('entity_fields',
            (new CheckboxListField(Locale::getMessage('BUILDER_UserTypeEntities_EntityFields')))
                ->setOptions($fields, 'ID', 'FIELD_TITLE', 'ENTITY_TITLE'),
        );

        $entities = $helper->UserTypeEntity()->exportUserTypeEntitiesByIds($fieldsIds);

        $this->confirmCreateVersion();

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
        $userTypeId = $this->askField('user_type_id',
            (new CheckboxListField(Locale::getMessage('BUILDER_UserTypeEntities_UserTypeId')))
                ->setInPlan(false)
                ->setOptions($this->getUserTypesSelect(), 'USER_TYPE_ID', 'USER_TITLE'),
        );

        return $this->getUserTypeEntities(['USER_TYPE_ID' => $userTypeId]);
    }

    /**
     * @throws RebuildException
     * @throws HelperException
     */
    private function askFieldsByEntityId(): array
    {
        $entityIds = $this->askField('entity_id',
            (new CheckboxListField(Locale::getMessage('BUILDER_UserTypeEntities_EntityId')))
                ->setInPlan(false)
                ->setOptions($this->getUserEntitiesSelect(), 'ENTITY_ID', 'ENTITY_TITLE', 'ENTITY_GROUP'),
        );

        return $this->getUserTypeEntities(['ENTITY_ID' => $entityIds]);
    }

    /**
     * @throws HelperException
     */
    private function getUserTypeEntities(array $filter = []): array
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
     * @throws HelperException
     */
    public function getUserEntitiesSelect(): array
    {
        $entityHelper = $this->getHelperManager()->UserTypeEntity();

        return array_map(function ($entityId) use ($entityHelper) {
            [$group] = explode('_', $entityId, 2);
            return [
                'ENTITY_GROUP' => $group ?: '-',
                'ENTITY_ID'    => $entityId,
                'ENTITY_TITLE' => $entityHelper->getEntityTitle($entityId),
            ];
        }, $entityHelper->getUserEntityIds());
    }

    /**
     * @throws HelperException
     */
    private function getUserTypesSelect(): array
    {
        $entityHelper = $this->getHelperManager()->UserTypeEntity();

        return array_map(function ($item) {
            $item['USER_TITLE'] = sprintf('[%s] %s', $item['USER_TYPE_ID'], $item['DESCRIPTION']);
            return $item;
        }, $entityHelper->getUserTypes());
    }
}
