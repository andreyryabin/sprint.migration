<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;

class UserTypeEntities extends VersionBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }


    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_UserTypeEntities1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_UserTypeEntities2'));

        $this->addField('prefix', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_PREFIX'),
            'value' => $this->getVersionConfig()->getVal('version_prefix'),
            'width' => 250,
        ));

        $this->addField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ));
    }


    protected function execute() {
        $helper = new HelperManager();

        $this->addField('type_codes', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_UserTypeEntities_EntityId'),
            'placeholder' => '',
            'width' => 250,
            'multiple' => 1,
            'items' => $this->getEntitiesStructure(),
            'value' => array()
        ));

        $typeCodes = $this->getFieldValue('type_codes');
        if (empty($typeCodes)) {
            $this->rebuildField('type_codes');
        }

        $typeCodes = is_array($typeCodes) ? $typeCodes : array($typeCodes);

        $entities = array();

        foreach ($typeCodes as $typeCode) {

            list($entityId, $fieldName) = explode(':', $typeCode);

            $entity = $helper->UserTypeEntity()->getUserTypeEntity($entityId, $fieldName);
            if (empty($entity)) {
                continue;
            }

            $fields = $entity;

            unset($fields['ID']);
            unset($fields['ENTITY_ID']);
            unset($fields['FIELD_NAME']);

            $entities[] = array(
                'ENTITY_ID' => $entity['ENTITY_ID'],
                'FIELD_NAME' => $entity['FIELD_NAME'],
                'FIELDS' => $fields
            );


        }


        $this->createVersionFile(Module::getModuleDir() . '/templates/UserTypeEntities.php', array(
            'entities' => $entities,
        ));
    }


    protected function getEntitiesStructure() {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CUserTypeEntity::GetList(array(), array());

        $structure = array();
        while ($item = $dbRes->Fetch()) {
            $entId = $item['ENTITY_ID'];

            if (!isset($structure[$entId])) {
                $structure[$entId] = array(
                    'title' => $entId,
                    'items' => array()
                );
            }

            $structure[$entId]['items'][] = array(
                'title' => $item['FIELD_NAME'],
                'value' => $entId . ':' . $item['FIELD_NAME']
            );
        }

        return $structure;

    }
}