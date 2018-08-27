<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;

class UserTypeEntities extends VersionBuilder
{

    public function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_UserTypeEntities1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_UserTypeEntities2'));

        $this->addField('type_codes', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_UserTypeEntities_EntityId'),
            'placeholder' => '',
            'width' => 250,
            'multiple' => 1,
            'items' => $this->getEntitiesStructure(),
            'value' => array()
        ));

        $this->addField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ));
    }


    public function execute() {
        $helper = new HelperManager();

        $typeCodes = $this->getFieldValue('type_codes');

        if (empty($typeCodes)){
            $this->rebuildField('type_codes');
        }

        $typeCodes = is_array($typeCodes) ? $typeCodes : array($typeCodes);

        $entities = array();

        foreach ($typeCodes as $typeCode) {

            list($entityId, $fieldName) = explode(':', $typeCode);

            $entity = $helper->UserTypeEntity()->getUserTypeEntity($entityId, $fieldName);
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

            if (!isset($structure[$entId])){
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