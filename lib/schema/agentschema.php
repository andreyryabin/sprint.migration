<?php

namespace Sprint\Migration\Schema;

use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Exceptions\HelperException;

class AgentSchema extends AbstractSchema
{

    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle('Схема агентов');
    }

    public function getMap()
    {
        return ['agents'];
    }

    public function outDescription()
    {
        $schemaItems = $this->loadSchema('agents', [
            'items' => [],
        ]);

        $this->out('Агенты: %d', count($schemaItems['items']));
    }


    public function export()
    {
        $helper = $this->getHelperManager();

        $exportItems = $helper->Agent()->exportAgents();

        $this->saveSchema('agents', [
            'items' => $exportItems,
        ]);
    }

    public function import()
    {
        $schemaItems = $this->loadSchema('agents', [
            'items' => [],
        ]);

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
     * @param $item
     * @throws HelperException
     */
    protected function saveAgent($item)
    {
        $helper = $this->getHelperManager();
        $helper->Agent()->setTestMode($this->testMode);
        $helper->Agent()->saveAgent($item);
    }

    /**
     * @param array $skip
     */
    protected function cleanAgents($skip = [])
    {
        $helper = $this->getHelperManager();

        $olds = $helper->Agent()->getList();
        foreach ($olds as $old) {
            $uniq = $this->getUniqAgent($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->Agent()->deleteAgent($old['MODULE_ID'], $old['NAME']);
                $this->outWarningIf($ok, 'Агент %s: удален', $old['NAME']);
            }
        }
    }

    protected function getUniqAgent($item)
    {
        return $item['MODULE_ID'] . $item['NAME'];
    }

}