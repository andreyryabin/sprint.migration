<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;
use Sprint\Migration\Helpers\AgentHelper;
use Sprint\Migration\HelperManager;

class GroupSchema extends AbstractSchema
{

    protected function isBuilderEnabled() {
        return true;
    }

    protected function initialize() {
        $this->setTitle('Схема групп пользователей');
    }

    public function outDescription() {
        $schemaGroups = $this->loadSchema('user_groups', array(
            'items' => array()
        ));

        $this->out('Группы пользователей: %d', count($schemaGroups['items']));
    }

    public function export() {
        $helper = new HelperManager();

        $this->deleteSchemas('user_groups');

        $exportAgents = $helper->UserGroup()->exportGroups();

        $this->saveSchema('user_groups', array(
            'items' => $exportAgents
        ));

        $this->outSchemas(array('user_groups'));
    }

    public function import() {
        $schemaGroups = $this->loadSchema('user_groups', array(
            'items' => array()
        ));

        foreach ($schemaGroups['items'] as $item) {
            $this->addToQueue('saveGroup', $item);
        }

        $skip = array();
        foreach ($schemaGroups['items'] as $item) {
            $skip[] = $this->getUniqGroup($item);
        }

        $this->addToQueue('cleanGroups', $skip);
    }


    protected function saveGroup($fields) {
        $helper = new HelperManager();
        $helper->UserGroup()->saveGroup($fields['STRING_ID'], $fields);
    }

    protected function cleanGroups($skip = array()) {
        $helper = new HelperManager();

        $olds = $helper->UserGroup()->getGroups();
        foreach ($olds as $old) {
            $uniq = $this->getUniqGroup($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->UserGroup()->deleteGroup($old['STRING_ID']);
                $this->outErrorIf($ok, 'Группа %s: удалена', $old['NAME']);
            }
        }
    }

    protected function getUniqGroup($item) {
        return $item['STRING_ID'];
    }

}