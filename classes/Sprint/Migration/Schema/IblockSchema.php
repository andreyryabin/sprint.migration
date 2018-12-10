<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Helpers\AdminIblockHelper;
use Sprint\Migration\Helpers\IblockHelper;

class IblockSchema extends AbstractSchema
{

    private $cache = array();

    protected function initialize() {
        $this->setTitle('Схема инфоблоков');
    }

    public function outDescription() {
        $schemaTypes = $this->loadSchema('iblock_types', array(
            'items' => array()
        ));

        $this->out('Типы инфоблоков: %d', count($schemaTypes['items']));

        $schemaIblocks = $this->loadSchemas('iblocks/', array(
            'iblock' => array(),
            'fields' => array(),
            'props' => array(),
            'element_form' => array()
        ));

        $this->out('Инфоблоков: %d', count($schemaIblocks));

        $cntProps = 0;
        $cntForms = 0;
        foreach ($schemaIblocks as $schemaIblock) {
            $cntProps += count($schemaIblock['props']);

            if (!empty($schemaIblock['element_form'])) {
                $cntForms++;
            }
        }

        $this->out('Свойств инфоблоков: %d', $cntProps);
        $this->out('Форм редактирования: %d', $cntForms);
    }

    public function export() {
        $iblockHelper = new IblockHelper();
        $adminHelper = new AdminIblockHelper();

        $this->deleteSchemas(array('iblock_types', 'iblocks/'));

        $types = $iblockHelper->getIblockTypes();
        $exportTypes = array();
        foreach ($types as $type) {
            $exportTypes[] = $iblockHelper->exportIblockType($type['ID']);
        }

        $this->saveSchema('iblock_types', array(
            'items' => $exportTypes
        ));

        $iblocks = $iblockHelper->getIblocks();
        foreach ($iblocks as $iblock) {
            if (!empty($iblock['CODE'])) {
                $this->saveSchema('iblocks/' . $iblock['IBLOCK_TYPE_ID'] . '-' . $iblock['CODE'], array(
                    'iblock' => $iblockHelper->exportIblock($iblock['ID']),
                    'fields' => $iblockHelper->exportIblockFields($iblock['ID']),
                    'props' => $iblockHelper->exportProperties($iblock['ID']),
                    'element_form' => $adminHelper->exportElementForm($iblock['ID'])
                ));
            }
        }

        $this->outSchemas(array('iblock_types', 'iblocks/'));
    }

    public function import() {
        $schemaTypes = $this->loadSchema('iblock_types', array(
            'items' => array()
        ));

        $schemaIblocks = $this->loadSchemas('iblocks/', array(
            'iblock' => array(),
            'fields' => array(),
            'props' => array(),
            'element_form' => array()
        ));

        foreach ($schemaTypes['items'] as $type) {
            $this->addToQueue('saveIblockType', $type);
        }

        foreach ($schemaIblocks as $schemaIblock) {
            $iblockId = $this->getIblockId($schemaIblock['iblock']);

            $this->addToQueue('saveIblock', $iblockId, $schemaIblock['iblock']);
            $this->addToQueue('saveIblockFields', $iblockId, $schemaIblock['fields']);
        }

        foreach ($schemaIblocks as $schemaIblock) {
            $iblockId = $this->getIblockId($schemaIblock['iblock']);

            foreach ($schemaIblock['props'] as $prop) {
                $this->addToQueue('saveProperty', $iblockId, $prop);
            }

            $this->addToQueue('saveElementForm', $iblockId, $schemaIblock['element_form']);
        }

        foreach ($schemaIblocks as $schemaIblock) {
            $iblockId = $this->getIblockId($schemaIblock['iblock']);

            $skip = array();
            foreach ($schemaIblock['props'] as $prop) {
                $skip[] = $this->getUniqProp($prop);
            }

            $this->addToQueue('cleanProperties', $iblockId, $skip);
        }

        $skip = array();
        foreach ($schemaIblocks as $schemaIblock) {
            $skip[] = $this->getUniqIblock($schemaIblock['iblock']);
        }

        $this->addToQueue('cleanIblocks', $skip);


        $skip = array();
        foreach ($schemaTypes['items'] as $type) {
            $skip[] = $this->getUniqIblockType($type);
        }

        $this->addToQueue('cleanIblockTypes', $skip);
    }


    protected function saveIblockType($fields = array()) {
        $iblockHelper = new IblockHelper();

        $iblockHelper->checkRequiredKeys(__METHOD__, $fields, array('ID'));

        $item = $iblockHelper->getIblockType($fields['ID']);
        $exportExists = $iblockHelper->prepareExportIblockType($item);
        $exportFields = $iblockHelper->prepareExportIblockType($fields);

        if (empty($item)) {
            $id = ($this->testMode) ? true : $iblockHelper->addIblockType($exportFields);
            $this->outSuccessIf($id, 'Тип инфоблока %s: добавлен', $fields['ID']);
            return $id;
        }


        if ($exportExists != $exportFields) {
            $id = ($this->testMode) ? true : $iblockHelper->updateIblockType($fields['ID'], $exportFields);
            $this->outSuccessIf($id, 'Тип инфоблока %s: обновлен', $fields['ID']);
            return $id;
        }

        $id = ($this->testMode) ? true : $fields['ID'];
        $this->outIf($id, 'Тип инфоблока %s: совпадает', $fields['ID']);

        return $id;
    }

    protected function saveIblock($iblockId, $fields) {
        $iblockHelper = new IblockHelper();
        $iblockHelper->checkRequiredKeys(__METHOD__, $fields, array('CODE', 'IBLOCK_TYPE_ID'));

        $item = $iblockHelper->getIblock($fields['CODE'], $fields['IBLOCK_TYPE_ID']);
        $exportExists = $iblockHelper->prepareExportIblock($item);
        $exportFields = $iblockHelper->prepareExportIblock($fields);


        if (empty($item)) {
            $id = ($this->testMode) ? true : $iblockHelper->addIblock($exportFields);
            $this->outSuccessIf($id, 'Инфоблок %s: добавлен', $fields['CODE']);
            return $id;
        }

        if ($exportExists != $exportFields) {
            $id = ($this->testMode) ? true : $iblockHelper->updateIblock($item['ID'], $exportFields);
            $this->outSuccessIf($id, 'Инфоблок %s: обновлен', $fields['CODE']);
            return $id;
        }

        $id = ($this->testMode) ? true : $item['ID'];
        $this->outIf($id, 'Инфоблок %s: совпадает', $fields['CODE']);
        return $id;
    }

    protected function saveIblockFields($iblockId, $fields) {
        $iblockHelper = new IblockHelper();

        $item = \CIBlock::GetFields($iblockId);

        $exportExists = $iblockHelper->prepareExportIblockFields($item);
        $exportFields = $iblockHelper->prepareExportIblockFields($fields);

        $exportFields = array_replace_recursive($exportExists, $exportFields);

        if (empty($item)) {
            $id = ($this->testMode) ? true : $iblockHelper->updateIblockFields($iblockId, $exportFields);
            $this->outSuccessIf($id, 'Инфоблок %s: поля добавлены', $iblockId);
            return $id;
        }

        if ($exportExists != $exportFields) {
            $id = ($this->testMode) ? true : $iblockHelper->updateIblockFields($iblockId, $exportFields);
            $this->outSuccessIf($id, 'Инфоблок %s: поля обновлены', $iblockId);
            return $id;
        }

        $this->outIf(true, 'Инфоблок %s: поля совпадают', $iblockId);
        return true;
    }

    protected function saveProperty($iblockId, $fields) {
        $iblockHelper = new IblockHelper();

        $iblockHelper->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        $item = $iblockHelper->getProperty($iblockId, $fields['CODE']);
        $exportExists = $iblockHelper->prepareExportProperty($item);
        $exportFields = $iblockHelper->prepareExportProperty($fields);

        if (empty($item)) {
            $id = ($this->testMode) ? true : $iblockHelper->addProperty($iblockId, $exportFields);
            $this->outSuccessIf($id, 'Инфоблок %s: свойство %s добавлено', $iblockId, $exportFields['CODE']);
            return $id;
        }


        if ($exportExists != $exportFields) {
            $id = ($this->testMode) ? true : $iblockHelper->updatePropertyById($item['ID'], $exportFields);
            $this->outSuccessIf($id, 'Инфоблок %s: свойство %s обновлено', $iblockId, $exportFields['CODE']);
            return $id;
        }

        $id = ($this->testMode) ? true : $item['ID'];
        $this->outIf($id, 'Инфоблок %s: свойство %s совпадает', $iblockId, $exportFields['CODE']);
        return $item['ID'];
    }

    protected function saveElementForm($iblockId, $elementForm) {
        $adminHelper = new AdminIblockHelper();

        $exists = $adminHelper->exportElementForm($iblockId);
        if ($exists != $elementForm) {
            $ok = ($this->testMode) ? true : $adminHelper->saveElementForm($iblockId, $elementForm);
            $this->outSuccessIf($ok, 'Инфоблок %s: форма редактирования сохранена', $iblockId);
        } else {
            $this->out('Инфоблок %s: форма редактирования cовпадает', $iblockId);
        }
    }


    protected function cleanProperties($iblockId, $skip = array()) {
        $iblockHelper = new IblockHelper();

        $olds = $iblockHelper->getProperties($iblockId);
        foreach ($olds as $old) {
            $uniq = $this->getUniqProp($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $iblockHelper->deletePropertyById($old['ID']);
                $this->outErrorIf($ok, 'Инфоблок %s: свойство %s удалено', $iblockId, $this->getTitleProp($old));
            }
        }
    }

    protected function cleanIblockTypes($skip = array()) {
        $iblockHelper = new IblockHelper();

        $olds = $iblockHelper->getIblockTypes();
        foreach ($olds as $old) {
            $uniq = $this->getUniqIblockType($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $iblockHelper->deleteIblockType($old['ID']);
                $this->outErrorIf($ok, 'Тип инфоблока %s: удален', $old['ID']);
            }
        }
    }

    protected function cleanIblocks($skip = array()) {
        $iblockHelper = new IblockHelper();

        $olds = $iblockHelper->getIblocks();
        foreach ($olds as $old) {
            $uniq = $this->getUniqIblock($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $iblockHelper->deleteIblock($old['ID']);
                $this->outError($ok, 'Инфоблок %s: удален', $old['ID']);
            }
        }
    }


    protected function getTitleProp($prop) {
        return empty($prop['CODE']) ? $prop['ID'] : $prop['CODE'];
    }

    protected function getUniqProp($prop) {
        return $prop['CODE'];
    }

    protected function getUniqIblockType($type) {
        return $type['ID'];
    }

    protected function getUniqIblock($iblock) {
        return $iblock['IBLOCK_TYPE_ID'] . $iblock['CODE'];
    }

    protected function getIblockId($iblock) {
        $iblockHelper = new IblockHelper();

        $uniq = $this->getUniqIblock($iblock);
        if (!isset($this->cache[$uniq])) {
            $this->cache[$uniq] = $iblockHelper->getIblockId(
                $iblock['CODE'],
                $iblock['IBLOCK_TYPE_ID']
            );
        }

        return $this->cache[$uniq];

    }

}