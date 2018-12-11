<?php

namespace Sprint\Migration\Schema;

use Sprint\Migration\AbstractSchema;
use Sprint\Migration\HelperManager;

class UserTypeEntitiesSchema extends AbstractSchema
{

    /** @var HelperManager */
    private $helper;

    protected function initialize() {
        $this->setTitle('Схема пользовательских полей');
        $this->helper = new HelperManager();
    }

    protected function isBuilderEnabled() {
        return true;
    }

    protected function UserTypeEntity() {
        return $this->helper->UserTypeEntity();
    }

    public function outDescription() {
        $schemaAgents = $this->loadSchema('user_type_entities', array(
            'items' => array()
        ));

        $this->out('Полей: %d', count($schemaAgents['items']));
    }

    public function export() {
        $this->deleteSchemas('user_type_entities');

        $exportItems = $this->UserTypeEntity()->exportUserTypeEntities(true);

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
        $this->UserTypeEntity()->checkRequiredKeys(__METHOD__, $fields, array('ENTITY_ID', 'FIELD_NAME'));

        $fields['ENTITY_ID'] = $this->UserTypeEntity()->revertEntityId(
            $fields['ENTITY_ID']
        );

        $exists = $this->UserTypeEntity()->getUserTypeEntity(
            $fields['ENTITY_ID'],
            $fields['FIELD_NAME']
        );

        $exportExists = $this->UserTypeEntity()->prepareExportUserTypeEntity($exists, false);
        $fields = $this->UserTypeEntity()->prepareExportUserTypeEntity($fields, false);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $this->UserTypeEntity()->addUserTypeEntity(
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
            $ok = ($this->testMode) ? true : $this->UserTypeEntity()->updateUserTypeEntity($exists['ID'], $fields);
            $this->outSuccessIf($ok, 'Пользовательское поле %s: обновлено', $fields['FIELD_NAME']);
            return $ok;
        }

        $ok = ($this->testMode) ? true : $exists['ID'];
        $this->outIf($ok, 'Пользовательское поле %s: совпадает', $fields['FIELD_NAME']);

        return $ok;

    }

    protected function clearUserTypeEntities($skip = array()) {
        $olds = $this->UserTypeEntity()->exportUserTypeEntities(true);

        foreach ($olds as $old) {
            $uniq = $this->getUniqEntity($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $this->UserTypeEntity()->deleteUserTypeEntity($old['ENTITY_ID'], $old['FIELD_NAME']);
                $this->outErrorIf($ok, 'Пользовательское поле %s: удалено', $old['FIELD_NAME']);
            }
        }
    }

    protected function getUniqEntity($item) {
        return $this->UserTypeEntity()->transformEntityId($item['ENTITY_ID']) . $item['FIELD_NAME'];
    }


}