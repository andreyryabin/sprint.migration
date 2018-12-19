<?php

namespace Sprint\Migration\Schema;

use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Helper;
use Sprint\Migration\HelperManager;

class UserTypeEntitiesSchema extends AbstractSchema
{

    private $transforms = array();

    protected function initialize() {
        $this->setTitle('Схема пользовательских полей');
    }

    protected function isBuilderEnabled() {
        return true;
    }

    public function outDescription() {
        $schemaItems = $this->loadSchema('user_type_entities', array(
            'items' => array()
        ));

        $this->out('Полей: %d', count($schemaItems['items']));
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
        $schemaItems = $this->loadSchema('user_type_entities', array(
            'items' => array()
        ));

        foreach ($schemaItems['items'] as $item) {
            $this->addToQueue('saveUserTypeEntity', $item);
        }


        $skip = array();
        foreach ($schemaItems['items'] as $item) {
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
                $this->outWarningIf($ok, 'Пользовательское поле %s: удалено', $old['FIELD_NAME']);
            }
        }
    }

    protected function getUniqEntity($item) {
        $entityId = $item['ENTITY_ID'];

        if (!isset($this->transforms[$entityId])) {
            $helper = new HelperManager();
            $this->transforms[$entityId] = $helper->UserTypeEntity()->transformEntityId($entityId);
        }

        return $this->transforms[$entityId] . $item['FIELD_NAME'];
    }


}