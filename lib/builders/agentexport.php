<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Exceptions\HelperException;

class AgentExport extends VersionBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }

    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_AgentExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_AgentExport2'));

        $this->addField('prefix', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_PREFIX'),
            'value' => $this->getVersionConfig()->getVal('version_prefix'),
            'width' => 250,
        ));

        $this->addField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ));
    }


    protected function execute() {
        $helper = HelperManager::getInstance();

        $this->addField('agent_id', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_AgentExport_agent_id'),
            'placeholder' => '',
            'multiple' => 1,
            'value' => array(),
            'width' => 250,
            'select' => $this->getAgents()
        ));

        $agentIds = $this->getFieldValue('agent_id');
        if (empty($agentIds)) {
            $this->rebuildField('agent_id');
        }

        $agentIds = is_array($agentIds) ? $agentIds : array($agentIds);

        $items = array();

        foreach ($agentIds as $agentId) {
            $agent = $helper->Agent()->exportAgent(array('ID' => $agentId));
            if (empty($agent)) {
                continue;
            }

            $items[] = $agent;
        }

        if (empty($items)) {
            $this->rebuildField('agent_id');
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/AgentExport.php', array(
            'items' => $items,
        ));

    }

    protected function getAgents() {
        $helper = HelperManager::getInstance();

        $agents = $helper->Agent()->getList(array());

        $result = [];
        foreach ($agents as $agent) {
            $result[] = [
                'title' => '[' . $agent['MODULE_ID'] . '] ' . $agent['NAME'],
                'value' => $agent['ID']
            ];
        }

        return $result;

    }
}