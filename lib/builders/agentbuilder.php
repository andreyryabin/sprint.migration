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
        $this->setGroup('Main');

        $this->addVersionFields();
    }

    protected function execute()
    {
        $helper = $this->getHelperManager();

        $agentIds = $this->addFieldAndReturn('agent_id', [
            'title'       => Locale::getMessage('BUILDER_AgentExport_agent_id'),
            'placeholder' => '',
            'multiple'    => 1,
            'value'       => [],
            'width'       => 250,
            'items'       => $this->getAgentsSelect(),
        ]);

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

    protected function getAgentsSelect(): array
    {
        return $this->createSelectWithGroups(
            $this->getHelperManager()->Agent()->getList(),
            'MODULE_ID',
            'ID',
            'NAME',
        );
    }
}
