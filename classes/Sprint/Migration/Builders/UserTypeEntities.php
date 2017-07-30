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
    }


    public function execute() {
        $helper = new HelperManager();

        $entityid = $this->getFieldValue('entity_id');
        $this->exitIfEmpty($entityid, 'Entities not found');

        $entities = $helper->UserTypeEntity()->getUserTypeEntities($entityid);
        $this->exitIfEmpty($entities, 'Entities not found');

        foreach ($entities as $index => $entity){
            unset($entity['ID']);
            $entities[$index] = $entity;
        }

        $this->setTemplateVar('entities', $entities);
    }
}