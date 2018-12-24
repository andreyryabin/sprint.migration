<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;
use Sprint\Migration\Helpers\AgentHelper;
use Sprint\Migration\HelperManager;

class AgentSchema extends AbstractSchema
{

    protected function isBuilderEnabled() {
        return true;
    }

    protected function initialize() {
        $this->setTitle('Схема агентов');
    }

    public function getMap() {
        return array('agents');
    }

    public function outDescription() {
        $schemaItems = $this->loadSchema('agents', array(
            'items' => array()
        ));

        $this->out('Агенты: %d', count($schemaItems['items']));
    }

    public function export() {
        $helper = HelperManager::getInstance();

        $exportItems = $helper->Agent()->exportAgents();

        $this->saveSchema('agents', array(
            'items' => $exportItems
        ));
    }

    public function import() {
        $schemaItems = $this->loadSchema('agents', array(
            'items' => array()
        ));

        foreach ($schemaItems['items'] as $item) {
            $this->addToQueue('saveAgent', $item);
        }

        $skip = array();
        foreach ($schemaItems['items'] as $item) {
            $skip[] = $this->getUniqAgent($item);
        }

        $this->addToQueue('cleanAgents', $skip);
    }


    protected function saveAgent($item) {
        $helper = HelperManager::getInstance();
        $helper->Agent()->setTestMode($this->testMode);
        $helper->Agent()->saveAgent($item);
    }

    protected function cleanAgents($skip = array()) {
        $helper = HelperManager::getInstance();

        $olds = $helper->Agent()->getList();
        foreach ($olds as $old) {
            $uniq = $this->getUniqAgent($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->Agent()->deleteAgent($old['MODULE_ID'], $old['NAME']);
                $this->outWarningIf($ok, 'Агент %s: удален', $old['NAME']);
            }
        }
    }

    protected function getUniqAgent($item) {
        return $item['MODULE_ID'] . $item['NAME'];
    }

}