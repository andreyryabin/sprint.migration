<?php

namespace Sprint\Migration\Schema;

use Sprint\Migration\AbstractSchema;
use Sprint\Migration\HelperManager;

class OptionSchema extends AbstractSchema
{

    private $transforms = [];

    protected function initialize()
    {
        $this->setTitle('Схема настроек модулей');
    }

    public function getMap()
    {
        return ['options/'];
    }

    protected function isBuilderEnabled()
    {
        $helper = HelperManager::getInstance();
        return $helper->Option()->isEnabled();
    }

    public function outDescription()
    {
        $schemas = $this->loadSchemas('options/', [
            'items' => [],
        ]);

        $cnt = 0;

        foreach ($schemas as $schema) {
            $cnt += count($schema['items']);
        }

        $this->out('Настроек: %d', $cnt);
    }

    public function export()
    {
        $helper = HelperManager::getInstance();

        $modules = $helper->Option()->getModules();

        foreach ($modules as $module) {
            $exportItems = $helper->Option()->getOptions([
                'MODULE_ID' => $module['ID'],
            ]);

            $this->saveSchema('options/' . $module['ID'], [
                'items' => $exportItems,
            ]);
        }
    }

    public function import()
    {
        $schemas = $this->loadSchemas('options/', [
            'items' => [],
        ]);

        foreach ($schemas as $schema) {
            $this->addToQueue('saveOptions', $schema['items']);
        }
    }


    protected function saveOptions($items)
    {
        $helper = HelperManager::getInstance();
        $helper->Option()->setTestMode($this->testMode);

        foreach ($items as $item) {
            $helper->Option()->saveOption($item);
        }
    }

}