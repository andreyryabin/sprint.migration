<?php

namespace Sprint\Migration\Schema;

use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Helper;
use Sprint\Migration\HelperManager;

class UserTypeEntitiesSchema extends AbstractSchema
{

    protected function initialize() {
        $this->setTitle('Схема пользовательских полей');
    }

    protected function isBuilderEnabled() {
        return true;
    }

    public function outDescription() {
        $schemaAgents = $this->loadSchema('user_type_entities', array(
            'items' => array()
        ));

        $this->out('Полей: %d', count($schemaAgents['items']));
    }

    public function export() {
        $helper = new HelperManager();
        $this->deleteSchemas('user_type_entities');

        $exportItems = $helper->UserTypeEntity()->exportUserTypeEntities(true);

        $this->saveSchema('user_type_entities', array(
            'items' => $exportItems
        ));

        $this->outSchemas(array('user_type_entities'));
    }

    public function import() {
        $schemaAgents = $this->loadSchema('user_type_entities', array(
            'items' => array()
        ));

        foreach ($schemaAgents['items'] as $item) {
            $this->addToQueue('saveUserTypeEntity', $item);
        }


        $skip = array();
        foreach ($schemaAgents['items'] as $item) {
            $skip[] = $this->getUniqEntity($item);
        }

        $this->addToQueue('clearUserTypeEntities', $skip);
    }


    protected function saveUserTypeEntity($fields) {
        $helper = new HelperManager();
        $helper->UserTypeEntity()->setTestMode($this->testMode);
        $helper->UserTypeEntity()->saveUserTypeEntity($fields['ENTITY_ID'], $fields['FIELD_NAME'], $fields);
    }

    protected function clearUserTypeEntities($skip = array()) {
        $helper = new HelperManager();
        $olds = $helper->UserTypeEntity()->exportUserTypeEntities(true);

        foreach ($olds as $old) {
            $uniq = $this->getUniqEntity($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->UserTypeEntity()->deleteUserTypeEntity($old['ENTITY_ID'], $old['FIELD_NAME']);
                $this->outErrorIf($ok, 'Пользовательское поле %s: удалено', $old['FIELD_NAME']);
            }
        }
    }

    protected function getUniqEntity($item) {
        $helper = new HelperManager();
        return $helper->UserTypeEntity()->transformEntityId($item['ENTITY_ID']) . $item['FIELD_NAME'];
    }


}