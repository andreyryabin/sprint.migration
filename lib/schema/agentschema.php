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

    public function outDescription() {
        $schemaAgents = $this->loadSchema('agents', array(
            'items' => array()
        ));

        $this->out('Агенты: %d', count($schemaAgents['items']));
    }

    public function export() {
        $helper = new HelperManager();

        $this->deleteSchemas('agents');

        $exportAgents = $helper->Agent()->exportAgents();

        $this->saveSchema('agents', array(
            'items' => $exportAgents
        ));

        $this->outSchemas(array('agents'));
    }

    public function import() {
        $schemaAgents = $this->loadSchema('agents', array(
            'items' => array()
        ));

        foreach ($schemaAgents['items'] as $item) {
            $this->addToQueue('saveAgent', $item);
        }

        $skip = array();
        foreach ($schemaAgents['items'] as $item) {
            $skip[] = $this->getUniqAgent($item);
        }

        $this->addToQueue('cleanAgents', $skip);
    }


    protected function saveAgent($item) {
        $helper = new HelperManager();
        $helper->Agent()->setTestMode($this->testMode);
        $helper->Agent()->saveAgent($item);
    }

    protected function cleanAgents($skip = array()) {
        $helper = new HelperManager();

        $olds = $helper->Agent()->getList();
        foreach ($olds as $old) {
            $uniq = $this->getUniqAgent($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->Agent()->deleteAgent($old['MODULE_ID'], $old['NAME']);
                $this->outErrorIf($ok, 'Агент %s: удален', $old['NAME']);
            }
        }
    }

    protected function getUniqAgent($item) {
        return $item['MODULE_ID'] . $item['NAME'];
    }

}