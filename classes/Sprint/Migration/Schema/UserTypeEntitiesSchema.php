<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;
use Sprint\Migration\HelperManager;

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

        $helper->UserTypeEntity()->checkRequiredKeys(__METHOD__, $fields, array('ENTITY_ID', 'FIELD_NAME'));

        $fields['ENTITY_ID'] = $helper->UserTypeEntity()->revertEntityId(
            $fields['ENTITY_ID']
        );

        $exists = $helper->UserTypeEntity()->getUserTypeEntity(
            $fields['ENTITY_ID'],
            $fields['FIELD_NAME']
        );

        $exportExists = $helper->UserTypeEntity()->prepareExportUserTypeEntity($exists, false);
        $fields = $helper->UserTypeEntity()->prepareExportUserTypeEntity($fields, false);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $helper->UserTypeEntity()->addUserTypeEntity(
                $fields['ENTITY_ID'],
                $fields['FIELD_NAME'],
                $fields
            );

            $this->outSuccessIf($ok, 'Пользовательское поле %s: добавлено', $fields['FIELD_NAME']);
            return $ok;
        }

        unset($exportExists['MULTIPLE']);
        unset($fields['MULTIPLE']);

        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $helper->UserTypeEntity()->updateUserTypeEntity($exists['ID'], $fields);
            $this->outSuccessIf($ok, 'Пользовательское поле %s: обновлено', $fields['FIELD_NAME']);
            return $ok;
        }

        $ok = ($this->testMode) ? true : $exists['ID'];
        $this->outIf($ok, 'Пользовательское поле %s: совпадает', $fields['FIELD_NAME']);

        return $ok;

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
        return $item['ENTITY_ID'] . $item['FIELD_NAME'];
    }

}