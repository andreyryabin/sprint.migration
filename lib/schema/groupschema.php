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

        foreach ($schemaGroups['items'] as $agent) {
            $this->addToQueue('saveGroup', $agent);
        }

        $skip = array();
        foreach ($schemaGroups['items'] as $agent) {
            $skip[] = $this->getUniqGroup($agent);
        }

        $this->addToQueue('cleanGroups', $skip);
    }


    protected function saveGroup($fields) {
        $helper = new HelperManager();
        $helper->UserGroup()->checkRequiredKeys(__METHOD__, $fields, array('STRING_ID', 'NAME'));


        $exists = $helper->UserGroup()->getGroup($fields['STRING_ID']);
        $exportExists = $helper->UserGroup()->prepareExportGroup($exists);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $helper->UserGroup()->addGroup($fields['STRING_ID'], $fields);
            $this->outSuccessIf($ok, 'Группа %s: добавлена', $fields['NAME']);
            return $ok;
        }

        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $helper->UserGroup()->updateGroup($exists['ID'], $fields);
            $this->outSuccessIf($ok, 'Группа %s: обновлена', $fields['NAME']);
            return $ok;
        }


        $ok = ($this->testMode) ? true : $exists['ID'];
        $this->outIf($ok, 'Группа %s: совпадает', $fields['NAME']);
        return $exists['ID'];
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