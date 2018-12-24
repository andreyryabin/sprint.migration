<?php

namespace Sprint\Migration\Schema;

use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Helper;
use Sprint\Migration\HelperManager;

class OptionSchema extends AbstractSchema
{

    private $transforms = array();

    protected function initialize() {
        $this->setTitle('Схема настроек модулей');
    }

    public function getMap(){
        return array('options/');
    }

    protected function isBuilderEnabled() {
        $helper = HelperManager::getInstance();
        return $helper->Option()->isEnabled();
    }

    public function outDescription() {
        $schemaItems = $this->loadSchema('user_type_entities', array(
            'items' => array()
        ));

        $this->out('Полей: %d', count($schemaItems['items']));
    }

    public function export() {
        $helper = HelperManager::getInstance();

        $exportItems = $helper->UserTypeEntity()->exportUserTypeEntities();
        $exportItems = $this->filterEntities($exportItems);

        $this->saveSchema('user_type_entities', array(
            'items' => $exportItems
        ));

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
        $helper = HelperManager::getInstance();
        $helper->UserTypeEntity()->setTestMode($this->testMode);
        $helper->UserTypeEntity()->saveUserTypeEntity($fields);
    }

    protected function clearUserTypeEntities($skip = array()) {
        $helper = HelperManager::getInstance();

        $olds = $helper->UserTypeEntity()->exportUserTypeEntities();
        $olds = $this->filterEntities($olds);

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
            $helper = HelperManager::getInstance();
            $this->transforms[$entityId] = $helper->UserTypeEntity()->transformEntityId($entityId);
        }

        return $this->transforms[$entityId] . $item['FIELD_NAME'];
    }

    protected function filterEntities($items = array()) {
        $filtered = array();
        foreach ($items as $item) {
            if (strpos($item['ENTITY_ID'], 'HLBLOCK_') === false) {
                $filtered[] = $item;
            }
        }
        return $filtered;
    }

}