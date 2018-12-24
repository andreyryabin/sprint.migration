<?php

namespace Sprint\Migration\Schema;

use Sprint\Migration\AbstractSchema;
use Sprint\Migration\Helper;
use Sprint\Migration\HelperManager;

class OptionSchema extends AbstractSchema
{

    private $transforms = array();

    protected function initialize() {
        $this->setTitle('Схема настроек модулей');
    }

    public function getMap() {
        return array('options/');
    }

    protected function isBuilderEnabled() {
        $helper = HelperManager::getInstance();
        return $helper->Option()->isEnabled();
    }

    public function outDescription() {
        $schemas = $this->loadSchemas('options/', array(
            'items' => array()
        ));

        $cnt = 0;

        foreach ($schemas as $schema) {
            $cnt += count($schema['items']);
        }

        $this->out('Настроек: %d', $cnt);
    }

    public function export() {
        $helper = HelperManager::getInstance();

        $modules = $helper->Option()->getModules();

        foreach ($modules as $module) {
            $exportItems = $helper->Option()->getOptions(array(
                'MODULE_ID' => $module['ID']
            ));

            $this->saveSchema('options/' . $module['ID'], array(
                'items' => $exportItems
            ));
        }
    }

    public function import() {
        $schemas = $this->loadSchemas('options/', array(
            'items' => array()
        ));

        foreach ($schemas as $schema) {
            $this->addToQueue('saveOptions', $schema['items']);
        }
    }


    protected function saveOptions($items) {
        $helper = HelperManager::getInstance();
        $helper->Option()->setTestMode($this->testMode);

        foreach ($items as $item) {
            $helper->Option()->saveOption($item);
        }
    }

}