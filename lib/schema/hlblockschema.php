<?php

namespace Sprint\Migration\Schema;

use Bitrix\Main\Loader;
use Sprint\Migration\AbstractSchema;
use Sprint\Migration\HelperManager;

class HlblockSchema extends AbstractSchema
{

    private $uniqs = [];

    protected function isBuilderEnabled()
    {
        return (Loader::includeModule('highloadblock'));
    }

    protected function initialize()
    {
        $this->setTitle('Схема highload-блоков');
    }

    public function getMap()
    {
        return ['hlblocks/'];
    }

    public function outDescription()
    {
        $schemas = $this->loadSchemas('hlblocks/', [
            'hlblock' => [],
            'fields' => [],
        ]);

        $cntFields = 0;
        foreach ($schemas as $schema) {
            $cntFields += count($schema['fields']);
        }

        $this->out('Highload-блоки: %d', count($schemas));
        $this->out('Полей: %d', $cntFields);
    }

    public function export()
    {
        $helper = HelperManager::getInstance();

        $exportItems = $helper->Hlblock()->exportHlblocks();

        foreach ($exportItems as $item) {
            $this->saveSchema('hlblocks/' . strtolower($item['NAME']), [
                'hlblock' => $item,
                'fields' => $helper->Hlblock()->exportFields($item['NAME']),
            ]);
        }
    }

    public function import()
    {
        $schemas = $this->loadSchemas('hlblocks/', [
            'hlblock' => [],
            'fields' => [],
        ]);

        foreach ($schemas as $schema) {
            $hlblockUid = $this->getUniqHlblock($schema['hlblock']);

            $this->addToQueue('saveHlblock', $schema['hlblock']);

            foreach ($schema['fields'] as $field) {
                $this->addToQueue('saveField', $hlblockUid, $field);
            }
        }

        foreach ($schemas as $schema) {
            $hlblockUid = $this->getUniqHlblock($schema['hlblock']);

            $skip = [];
            foreach ($schema['fields'] as $field) {
                $skip[] = $this->getUniqField($field);
            }

            $this->addToQueue('cleanFields', $hlblockUid, $skip);
        }

        $skip = [];
        foreach ($schemas as $schema) {
            $skip[] = $this->getUniqHlblock($schema['hlblock']);
        }

        $this->addToQueue('cleanHlblocks', $skip);
    }


    protected function saveHlblock($item)
    {
        $helper = HelperManager::getInstance();
        $helper->Hlblock()->setTestMode($this->testMode);
        $helper->Hlblock()->saveHlblock($item);
    }

    protected function saveField($hlblockUid, $field)
    {
        $hlblockId = $this->getHlblockId($hlblockUid);
        if (!empty($hlblockId)) {
            $helper = HelperManager::getInstance();
            $helper->Hlblock()->setTestMode($this->testMode);
            $helper->Hlblock()->saveField($hlblockId, $field);
        }
    }

    protected function cleanHlblocks($skip = [])
    {
        $helper = HelperManager::getInstance();

        $olds = $helper->Hlblock()->getHlblocks();
        foreach ($olds as $old) {
            $uniq = $this->getUniqHlblock($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $helper->Hlblock()->deleteHlblock($old['ID']);
                $this->outWarningIf($ok, 'Highload-блок %s: удален', $old['NAME']);
            }
        }
    }

    protected function cleanFields($hlblockUid, $skip = [])
    {
        $hlblockId = $this->getHlblockId($hlblockUid);
        if (!empty($hlblockId)) {
            $helper = HelperManager::getInstance();
            $olds = $helper->Hlblock()->getFields($hlblockId);
            foreach ($olds as $old) {
                $uniq = $this->getUniqField($old);
                if (!in_array($uniq, $skip)) {
                    $ok = ($this->testMode) ? true : $helper->Hlblock()->deleteField($hlblockId, $old['FIELD_NAME']);
                    $this->outWarningIf($ok, 'Поле highload-блока %s: удалено', $old['FIELD_NAME']);
                }
            }
        }
    }

    protected function getHlblockId($hlblockUid)
    {
        $helper = HelperManager::getInstance();

        if (isset($this->uniqs[$hlblockUid])) {
            return $this->uniqs[$hlblockUid];
        }

        list($tableName, $hlblockName) = explode(':', $hlblockUid);

        $this->uniqs[$hlblockUid] = $helper->Hlblock()->getHlblockId($hlblockName);
        return $this->uniqs[$hlblockUid];
    }

    protected function getUniqField($item)
    {
        return $item['FIELD_NAME'];
    }

    protected function getUniqHlblock($item)
    {
        return $item['TABLE_NAME'] . ':' . $item['NAME'];
    }


}