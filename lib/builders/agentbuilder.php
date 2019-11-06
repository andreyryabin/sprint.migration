<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class AgentBuilder extends VersionBuilder
{

    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_AgentExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_AgentExport2'));
        $this->addVersionFields();
    }


    protected function execute()
    {
        $helper = $this->getHelperManager();

        $this->addField('agent_id', [
            'title' => Locale::getMessage('BUILDER_AgentExport_agent_id'),
            'placeholder' => '',
            'multiple' => 1,
            'value' => [],
            'width' => 250,
            'select' => $this->getAgents(),
        ]);

        $agentIds = $this->getFieldValue('agent_id');
        if (empty($agentIds)) {
            $this->rebuildField('agent_id');
        }

        $agentIds = is_array($agentIds) ? $agentIds : [$agentIds];

        $items = [];

        foreach ($agentIds as $agentId) {
            $agent = $helper->Agent()->exportAgent(['ID' => $agentId]);
            if (empty($agent)) {
                continue;
            }

            $items[] = $agent;
        }

        if (empty($items)) {
            $this->rebuildField('agent_id');
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/AgentExport.php',
            [
                'items' => $items,
            ]
        );

    }

    protected function getAgents()
    {
        $helper = $this->getHelperManager();

        $agents = $helper->Agent()->getList([]);

        $result = [];
        foreach ($agents as $agent) {
            $result[] = [
                'title' => '[' . $agent['MODULE_ID'] . '] ' . $agent['NAME'],
                'value' => $agent['ID'],
            ];
        }

        return $result;

    }
}