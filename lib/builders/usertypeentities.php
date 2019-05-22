<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;

class UserTypeEntities extends VersionBuilder
{

    protected function isBuilderEnabled()
    {
        return true;
    }


    protected function initialize()
    {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_UserTypeEntities1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_UserTypeEntities2'));

        $this->addField('prefix', [
            'title' => GetMessage('SPRINT_MIGRATION_FORM_PREFIX'),
            'value' => $this->getVersionConfig()->getVal('version_prefix'),
            'width' => 250,
        ]);

        $this->addField('description', [
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ]);
    }


    protected function execute()
    {
        $helper = HelperManager::getInstance();

        $this->addField('type_codes', [
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_UserTypeEntities_EntityId'),
            'placeholder' => '',
            'width' => 250,
            'multiple' => 1,
            'items' => $this->getEntitiesStructure(),
            'value' => [],
        ]);

        $typeCodes = $this->getFieldValue('type_codes');
        if (empty($typeCodes)) {
            $this->rebuildField('type_codes');
        }

        $typeCodes = is_array($typeCodes) ? $typeCodes : [$typeCodes];

        $entities = [];

        foreach ($typeCodes as $fieldId) {
            $entity = $helper->UserTypeEntity()->exportUserTypeEntity($fieldId);
            if (!empty($entity)) {
                $entities[] = $entity;
            }
        }

        $this->createVersionFile(Module::getModuleDir() . '/templates/UserTypeEntities.php', [
            'entities' => $entities,
        ]);
    }


    protected function getEntitiesStructure()
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CUserTypeEntity::GetList([], []);

        $structure = [];
        while ($item = $dbRes->Fetch()) {
            $entId = $item['ENTITY_ID'];

            if (!isset($structure[$entId])) {
                $structure[$entId] = [
                    'title' => $entId,
                    'items' => [],
                ];
            }

            $structure[$entId]['items'][] = [
                'title' => $item['FIELD_NAME'],
                'value' => $item['ID'],
            ];
        }

        return $structure;

    }
}