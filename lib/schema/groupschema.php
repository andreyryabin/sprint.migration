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

    public function getMap() {
        return array('user_groups');
    }

    public function outDescription() {
        $schemaItems = $this->loadSchema('user_groups', array(
            'items' => array()
        ));

        $this->out('Группы пользователей: %d', count($schemaItems['items']));
    }

    public function export() {
        $helper = HelperManager::getInstance();

        $exportItems = $helper->UserGroup()->exportGroups();

        $this->saveSchema('user_groups', array(
            'items' => $exportItems
        ));
    }

    public function import() {
        $schemaItems = $this->loadSchema('user_groups', array(
            'items' => array()
        ));

        foreach ($schemaItems['items'] as $item) {
            $this->addToQueue('saveGroup', $item);
        }

        $skip = array();
        foreach ($schemaItems['items'] as $item) {
            $skip[] = $this->getUniqGroup($item);
        }

        $this->addToQueue('cleanGroups', $skip);
    }


    protected function saveGroup($fields) {
        $helper = HelperManager::getInstance();
        $helper->UserGroup()->setTestMode($this->testMode);
        $helper->UserGroup()->saveGroup($fields['STRING_ID'], $fields);
    }

    protected function cleanGroups($skip = array()) {
        $helper = HelperManager::getInstance();

        $olds = $helper->UserGroup()->getGroups();
        foreach ($olds as $old) {
            if (!empty($old['STRING_ID'])) {
                $uniq = $this->getUniqGroup($old);
                if (!in_array($uniq, $skip)) {
                    $ok = ($this->testMode) ? true : $helper->UserGroup()->deleteGroup($old['STRING_ID']);
                    $this->outWarningIf($ok, 'Группа %s: удалена', $old['NAME']);
                }
            }
        }
    }

    protected function getUniqGroup($item) {
        return $item['STRING_ID'];
    }

}