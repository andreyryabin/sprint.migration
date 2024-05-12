<?php

namespace Sprint\Migration\Schema;

use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;

class AgentSchema extends AbstractSchema
{
    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('SCHEMA_AGENT'));
    }

    public function getMap()
    {
        return ['agents'];
    }

    public function outDescription()
    {
        $schemaItems = $this->loadSchema(
            'agents', [
                'items' => [],
            ]
        );

        $this->out(
            Locale::getMessage(
                'SCHEMA_AGENT_DESC',
                [
                    '#COUNT#' => count($schemaItems['items']),
                ]
            )
        );
    }

    public function export()
    {
        $helper = $this->getHelperManager();

        $exportItems = $helper->Agent()->exportAgents();

        $this->saveSchema(
            'agents', [
                'items' => $exportItems,
            ]
        );
    }

    public function import()
    {
        $schemaItems = $this->loadSchema(
            'agents', [
                'items' => [],
            ]
        );

        foreach ($schemaItems['items'] as $item) {
            $this->addToQueue('saveAgent', $item);
        }

        $skip = [];
        foreach ($schemaItems['items'] as $item) {
            $skip[] = $this->getUniqAgent($item);
        }

        $this->addToQueue('cleanAgents', $skip);
    }

    /**
     * @throws HelperException
     */
    protected function saveAgent($item)
    {
        $agentHelper = $this->getHelperManager()->Agent()->setTestMode(
            $this->isTestMode()
        );

        $agentHelper->saveAgent($item);
    }

    protected function cleanAgents($skip)
    {
        $agentHelper = $this->getHelperManager()->Agent()->setTestMode(
            $this->isTestMode()
        );

        $olds = $agentHelper->getList();
        foreach ($olds as $old) {
            $uniq = $this->getUniqAgent($old);
            if (!in_array($uniq, $skip)) {
                $ok = $agentHelper->deleteAgent($old['MODULE_ID'], $old['NAME']);
                $this->outWarningIf(
                    $ok,
                    Locale::getMessage(
                        'AGENT_DELETED',
                        [
                            '#NAME#' => $old['NAME'],
                        ]
                    )
                );
            }
        }
    }

    protected function getUniqAgent($item)
    {
        return $item['MODULE_ID'] . $item['NAME'];
    }
}
