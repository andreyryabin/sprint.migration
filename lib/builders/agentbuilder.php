<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class AgentBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return true;
    }

    protected function initialize(): void
    {
        $this->setTitle(Locale::getMessage('BUILDER_AgentExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_AgentExport2'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Main'));

        $this->addVersionFields();
    }

    /**
     * @throws MigrationException
     * @throws RebuildException
     * @throws HelperException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $agents = array_map(fn($item) => [
            'MODULE_ID' => $item['MODULE_ID'] ?: Locale::getMessage('BUILDER_AgentExport_empty_module'),
            'ID'        => $item['ID'],
            'NAME'      => $item['NAME'],
        ], $helper->Agent()->getList());

        $agentIds = $this->askField('agent_id',
            (new CheckboxListField(Locale::getMessage('BUILDER_AgentExport_agent_id')))
            ->setOptions($agents, 'ID', 'NAME', 'MODULE_ID')
        );

        $items = array_map(
            fn($agentId) => $helper->Agent()->exportAgentById($agentId),
            $agentIds,
        );

        if (empty($items)) {
            $this->resetField('agent_id');
        }

        $this->confirmCreateVersion();

        $this->createVersionFile(
            Module::getModuleTemplateFile('AgentExport'),
            [
                'items' => $items,
            ],
        );
    }
}
