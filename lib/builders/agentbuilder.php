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
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Main'));

        $this->addVersionFields();
    }

    protected function execute()
    {
        $helper = $this->getHelperManager();

        $allAgents = array_map(function ($item) {
            $item['MODULE_ID'] = $item['MODULE_ID'] ?: Locale::getMessage('BUILDER_AgentExport_empty_module');
            return $item;
        }, $this->getHelperManager()->Agent()->getList());

        $moduleIds = $this->addFieldAndReturn('module_id', [
            'title'       => Locale::getMessage('BUILDER_AgentExport_module_id'),
            'placeholder' => '',
            'multiple'    => 1,
            'value'       => [],
            'width'       => 250,
            'select'      => $this->createSelect($allAgents, 'MODULE_ID', 'MODULE_ID'),
        ]);

        $selectAgents = array_filter($allAgents, function ($item) use ($moduleIds) {
            return in_array($item['MODULE_ID'], $moduleIds);
        });

        $agentIds = $this->addFieldAndReturn('agent_id', [
            'title'       => Locale::getMessage('BUILDER_AgentExport_agent_id'),
            'placeholder' => '',
            'multiple'    => 1,
            'value'       => [],
            'width'       => 250,
            'items'       => $this->createSelectWithGroups($selectAgents, 'ID', 'NAME', 'MODULE_ID'),
        ]);

        $items = [];
        foreach ($agentIds as $agentId) {
            $agent = $helper->Agent()->exportAgentById($agentId);
            if (!empty($agent)) {
                $items[] = $agent;;
            }
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
}
