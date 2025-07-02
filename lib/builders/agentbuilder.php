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

        $agents = array_map(fn($item) => [
            'MODULE_ID' => $item['MODULE_ID'] ?: Locale::getMessage('BUILDER_AgentExport_empty_module'),
            'ID'        => $item['ID'],
            'NAME'      => $item['NAME'],
        ], $helper->Agent()->getList());

        $agentIds = $this->addFieldAndReturn('agent_id', [
            'title'       => Locale::getMessage('BUILDER_AgentExport_agent_id'),
            'placeholder' => '',
            'multiple'    => 1,
            'value'       => [],
            'width'       => 250,
            'items'       => $this->createSelectWithGroups($agents, 'ID', 'NAME', 'MODULE_ID'),
        ]);

        $items = array_map(
            fn($agentId) => $helper->Agent()->exportAgentById($agentId),
            $agentIds
        );

        if (empty($items)) {
            $this->rebuildField('agent_id');
        }

        $this->createVersionFile(
            Module::getModuleTemplateFile('AgentExport'),
            [
                'items' => $items,
            ]
        );
    }
}
