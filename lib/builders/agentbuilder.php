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

        $field1 = (new CheckboxListField(Locale::getMessage('BUILDER_AgentExport_agent_id')));
        foreach ($helper->Agent()->getList() as $item) {
            $field1->addOption(
                $item['ID'],
                $item['NAME'],
                $item['MODULE_ID'] ?: Locale::getMessage('BUILDER_AgentExport_empty_module'),
            );
        }

        $agentIds = $this->askField('agent_id', $field1);

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
