<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;
use Sprint\Migration\Helpers\AgentHelper;
use Sprint\Migration\HelperManager;

class HlblockSchema extends AbstractSchema
{

    private $uniqs = array();

    protected function isBuilderEnabled() {
        return (\CModule::IncludeModule('highloadblock'));
    }

    protected function initialize() {
        $this->setTitle('Схема highload-блоков');
    }

    public function outDescription() {
        $schemas = $this->loadSchemas('hlblocks/', array(
            'hlblock' => array(),
            'fields' => array(),
        ));

        $cntFields = 0;
        foreach ($schemas as $schema) {
            $cntFields += count($schema['fields']);
        }

        $this->out('Highload-блоки: %d', count($schemas));
        $this->out('Полей: %d', $cntFields);
    }

    public function export() {
        $helper = new HelperManager();

        $this->deleteSchemas('hlblocks/');

        $exportItems = $helper->Hlblock()->exportHlblocks();

        foreach ($exportItems as $item) {
            $this->saveSchema('hlblocks/' . strtolower($item['NAME']), array(
                'hlblock' => $item,
                'fields' => $helper->Hlblock()->exportFields($item['NAME'])
            ));
        }

        $this->outSchemas(array('hlblocks/'));
    }

    public function import() {
        $schemas = $this->loadSchemas('hlblocks/', array(
            'hlblock' => array(),
            'fields' => array(),
        ));

        foreach ($schemas as $schema) {
            $hlblockId = $this->getHlblockId($schema['hlblock']);

            $this->addToQueue('saveHlblock', $schema['hlblock']);

            foreach ($schema['fields'] as $field) {
                $this->addToQueue('saveField', $hlblockId, $field);
            }
        }

        foreach ($schemas as $schema) {
            $hlblockId = $this->getHlblockId($schema['hlblock']);

            $skip = array();
            foreach ($schema['fields'] as $field) {
                $skip[] = $this->getUniqField($field);
            }

            $this->addToQueue('cleanFields', $hlblockId, $skip);
        }

        $skip = array();
        foreach ($schemas as $schema) {
            $skip[] = $this->getUniqHlblock($schema['hlblock']);
        }

        $this->addToQueue('cleanHlblocks', $skip);
    }


    protected function saveHlblock($item) {
        $helper = new HelperManager();
        $helper->Hlblock()->setTestMode($this->testMode);
        $helper->Hlblock()->saveHlblock($item);
    }

    protected function saveField($hlblockId, $field) {
        $helper = new HelperManager();
        $helper->Hlblock()->setTestMode($this->testMode);
        $helper->Hlblock()->saveField($hlblockId, $field);
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

    protected function cleanFields($hlblockId, $skip = array()) {
        $helper = new HelperManager();

        $olds = $helper->Hlblock()->getFields($hlblockId);
        foreach ($olds as $old) {
            $uniq = $this->getUniqField($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->Hlblock()->deleteField($hlblockId, $old['FIELD_NAME']);
                $this->outWarningIf($ok, 'Поле highload-блока %s: удалено', $old['FIELD_NAME']);
            }
        }
    }

    protected function getHlblockId($hlblock) {
        $helper = new HelperManager();

        $uniq = $this->getUniqHlblock($hlblock);
        if (isset($this->uniqs[$uniq])) {
            return $this->uniqs[$uniq];
        }

        $this->uniqs[$uniq] = $helper->Hlblock()->getHlblockIdIfExists(
            $hlblock['NAME']
        );
        return $this->uniqs[$uniq];
    }

    protected function getUniqField($item) {
        return $item['FIELD_NAME'];
    }

    protected function getUniqHlblock($item) {
        return $item['TABLE_NAME'];
    }


}