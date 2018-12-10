<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;
use Sprint\Migration\Helpers\AgentHelper;

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
        $agentHelper = new AgentHelper();
        
        $this->deleteSchemas('agents');

        $exportAgents = $agentHelper->exportAgents();

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
        $agentHelper = new AgentHelper();
        
        $exists = $agentHelper->exportAgent(array(
            'MODULE_ID' => $agent['MODULE_ID'],
            'NAME' => $agent['NAME']
        ));

        if ($exists != $agent) {

            if (!$this->testMode) {
                $agentHelper->saveAgent($agent);
            }

            $this->outSuccess('Агент %s: сохранен', $agent['NAME']);
        } else {
            $this->out('Агент %s: совпадает', $agent['NAME']);
        }
    }

    protected function cleanAgents($skip = array()) {
        $agentHelper = new AgentHelper();
        
        $olds = $agentHelper->getList();
        foreach ($olds as $old) {
            $uniq = $this->getUniqAgent($old);
            if (!in_array($uniq, $skip)) {
                if (!$this->testMode) {
                    $agentHelper->deleteAgent($old['MODULE_ID'], $old['NAME']);
                }
                $this->outError('Агент %s: удален', $old['NAME']);
            }
        }
    }

    protected function getUniqAgent($item) {
        return $item['MODULE_ID'] . $item['NAME'];
    }

}