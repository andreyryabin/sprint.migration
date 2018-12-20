<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;
use Sprint\Migration\Helpers\AgentHelper;
use Sprint\Migration\HelperManager;

class HlblockSchema extends AbstractSchema
{

    protected function isBuilderEnabled() {
        return (\CModule::IncludeModule('highloadblock'));
    }

    protected function initialize() {
        $this->setTitle('Схема highload-блоков');
    }

    public function outDescription() {
        $schemaItems = $this->loadSchema('hlblocks', array(
            'items' => array()
        ));

        $this->out('Highload-блоки: %d', count($schemaItems['items']));
    }

    public function export() {
        $helper = new HelperManager();

        $this->deleteSchemas('hlblocks');

        $exportItems = $helper->Hlblock()->exportHlblocks();

        $this->saveSchema('hlblocks', array(
            'items' => $exportItems
        ));

        $this->outSchemas(array('hlblocks'));
    }

    public function import() {
        $schemaItems = $this->loadSchema('hlblocks', array(
            'items' => array()
        ));

        foreach ($schemaItems['items'] as $item) {
            $this->addToQueue('saveHlblock', $item);
        }

        $skip = array();
        foreach ($schemaItems['items'] as $item) {
            $skip[] = $this->getUniqHlblock($item);
        }

        $this->addToQueue('cleanHlblocks', $skip);
    }


    protected function saveHlblock($item) {
        $helper = new HelperManager();
        $helper->Hlblock()->setTestMode($this->testMode);
        $helper->Hlblock()->saveHlblock($item);
    }

    protected function cleanHlblocks($skip = array()) {
        $helper = new HelperManager();

        $olds = $helper->Hlblock()->getHlblocks();
        foreach ($olds as $old) {
            $uniq = $this->getUniqHlblock($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->Hlblock()->deleteHlblock($old['ID']);
                $this->outWarningIf($ok, 'Highload-блок %s: удален', $old['NAME']);
            }
        }
    }

    protected function getUniqHlblock($item) {
        return $item['TABLE_NAME'];
    }

}