<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;

class UserTypeEntitiesSchema extends AbstractSchema
{

    protected function initialize() {
        $this->setTitle('Схема пользовательских полей');
    }

    public function outDescription() {
        $schemaAgents = $this->loadSchema('user_type_entities', array(
            'items' => array()
        ));

        $this->out('Полей: %d', count($schemaAgents['items']));
    }

    public function export() {
        $this->deleteSchemas('user_type_entities');

        $exportItems = $this->helper->UserTypeEntity()->exportUserTypeEntities();

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


    protected function saveUserTypeEntity($item) {
        $exists = $this->helper->UserTypeEntity()->exportUserTypeEntity($item['ENTITY_ID'], $item['FIELD_NAME']);
        if ($exists != $item) {
            if (!$this->testMode) {
                $this->helper->UserTypeEntity()->saveUserTypeEntity($item['ENTITY_ID'], $item['FIELD_NAME'], $item);
            }
            $this->outSuccess('Пользовательское поле %s: сохранено', $item['FIELD_NAME']);
        } else {
            $this->out('Пользовательское поле %s: совпадает', $item['FIELD_NAME']);
        }
    }

    protected function clearUserTypeEntities($skip = array()) {
        $olds = $this->helper->UserTypeEntity()->getUserTypeEntities();
        foreach ($olds as $old) {
            $uniq = $this->getUniqEntity($old);
            if (!in_array($uniq, $skip)) {
                if (!$this->testMode) {
                    $this->helper->UserTypeEntity()->deleteUserTypeEntity($old['ENTITY_ID'], $old['FIELD_NAME']);
                }
                $this->outError('Пользовательское поле %s: удалено', $old['FIELD_NAME']);
            }
        }
    }

    protected function getUniqEntity($item) {
        return $item['ENTITY_ID'] . $item['FIELD_NAME'];
    }

}