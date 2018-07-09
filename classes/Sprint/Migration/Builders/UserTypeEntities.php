<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\HelperManager;

class UserTypeEntities extends AbstractBuilder
{

    public function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_UserTypeEntities1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_UserTypeEntities2'));
        $this->setTemplateFile(Module::getModuleDir() . '/templates/UserTypeEntities.php');

        $this->setField('entity_id', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_UserTypeEntities_EntityId'),
            'placeholder' => ''
        ));

        $this->setField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ));
    }


    public function execute() {
        $helper = new HelperManager();

        $entityid = $this->getFieldValue('entity_id');
        $this->exitIfEmpty($entityid, 'Entities not found');

        $entities = $helper->UserTypeEntity()->getUserTypeEntities($entityid);
        $this->exitIfEmpty($entities, 'Entities not found');

        foreach ($entities as $index => $entity){

            $fields = $entity;

            unset($fields['ID']);
            unset($fields['ENTITY_ID']);
            unset($fields['FIELD_NAME']);

            $entities[$index] = array(
                'ENTITY_ID' => $entity['ENTITY_ID'],
                'FIELD_NAME' => $entity['FIELD_NAME'],
                'FIELDS' => $fields
            );
        }

        $this->setTemplateVar('entities', $entities);
    }
}