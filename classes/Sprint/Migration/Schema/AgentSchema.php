<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;
use Sprint\Migration\Helpers\AgentHelper;
use Sprint\Migration\HelperManager;

class AgentSchema extends AbstractSchema
{

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

        foreach ($schemaAgents['items'] as $agent) {
            $this->addToQueue('saveAgent', $agent);
        }

        $skip = array();
        foreach ($schemaAgents['items'] as $agent) {
            $skip[] = $this->getUniqAgent($agent);
        }

        $this->addToQueue('cleanAgents', $skip);
    }


    protected function saveAgent($agent) {
        $helper = new HelperManager();

        $exists = $helper->Agent()->exportAgent(array(
            'MODULE_ID' => $agent['MODULE_ID'],
            'NAME' => $agent['NAME']
        ));

        if ($exists != $agent) {
            $ok = ($this->testMode) ? true : $helper->Agent()->saveAgent($agent);
            $this->outSuccessIf($ok, 'Агент %s: сохранен', $agent['NAME']);
        } else {
            $this->out('Агент %s: совпадает', $agent['NAME']);
        }
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