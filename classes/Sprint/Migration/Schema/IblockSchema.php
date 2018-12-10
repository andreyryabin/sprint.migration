<?php

namespace Sprint\Migration\Schema;

use \Sprint\Migration\AbstractSchema;

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
        $this->deleteSchemas(array('iblock_types', 'iblocks/'));

        $types = $this->helper->Iblock()->getIblockTypes();
        $exportTypes = array();
        foreach ($types as $type) {
            $exportTypes[] = $this->helper->Iblock()->exportIblockType($type['ID']);
        }

        $this->saveSchema('iblock_types', array(
            'items' => $exportTypes
        ));

        $iblocks = $this->helper->Iblock()->getIblocks();
        foreach ($iblocks as $iblock) {
            if (!empty($iblock['CODE'])) {
                $this->saveSchema('iblocks/' . $iblock['IBLOCK_TYPE_ID'] . '-' . $iblock['CODE'], array(
                    'iblock' => $this->helper->Iblock()->exportIblock($iblock['ID']),
                    'fields' => $this->helper->Iblock()->exportIblockFields($iblock['ID']),
                    'props' => $this->helper->Iblock()->exportProperties($iblock['ID']),
                    'element_form' => $this->helper->AdminIblock()->exportElementForm($iblock['ID'])
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
        $this->helper->Iblock()->checkRequiredKeys(__METHOD__, $fields, array('ID'));

        $exists = $this->helper->Iblock()->getIblockType($fields['ID']);
        $exportExists = $this->helper->Iblock()->prepareExportIblockType($exists);
        $fields = $this->helper->Iblock()->prepareExportIblockType($fields);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $this->helper->Iblock()->addIblockType($fields);
            $this->outSuccessIf($ok, 'Тип инфоблока %s: добавлен', $fields['ID']);
            return $ok;
        }


        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $this->helper->Iblock()->updateIblockType($exists['ID'], $fields);
            $this->outSuccessIf($ok, 'Тип инфоблока %s: обновлен', $fields['ID']);
            return $ok;
        }

        $ok = ($this->testMode) ? true : $fields['ID'];
        $this->outIf($ok, 'Тип инфоблока %s: совпадает', $fields['ID']);

        return $ok;
    }

    protected function saveIblock($iblockId, $fields) {
        $this->helper->Iblock()->checkRequiredKeys(__METHOD__, $fields, array('CODE', 'IBLOCK_TYPE_ID'));

        $exists = $this->helper->Iblock()->getIblock($fields['CODE'], $fields['IBLOCK_TYPE_ID']);
        $exportExists = $this->helper->Iblock()->prepareExportIblock($exists);
        $fields = $this->helper->Iblock()->prepareExportIblock($fields);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $this->helper->Iblock()->addIblock($fields);
            $this->outSuccessIf($ok, 'Инфоблок %s: добавлен', $fields['CODE']);
            return $ok;
        }

        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $this->helper->Iblock()->updateIblock($exists['ID'], $fields);
            $this->outSuccessIf($ok, 'Инфоблок %s: обновлен', $fields['CODE']);
            return $ok;
        }

        $ok = ($this->testMode) ? true : $exists['ID'];
        $this->outIf($ok, 'Инфоблок %s: совпадает', $fields['CODE']);
        return $ok;
    }

    protected function saveIblockFields($iblockId, $fields) {
        $exists = \CIBlock::GetFields($iblockId);

        $exportExists = $this->helper->Iblock()->prepareExportIblockFields($exists);
        $fields = $this->helper->Iblock()->prepareExportIblockFields($fields);

        $fields = array_replace_recursive($exportExists, $fields);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $this->helper->Iblock()->updateIblockFields($iblockId, $fields);
            $this->outSuccessIf($ok, 'Инфоблок %s: поля добавлены', $iblockId);
            return $ok;
        }

        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $this->helper->Iblock()->updateIblockFields($iblockId, $fields);
            $this->outSuccessIf($ok, 'Инфоблок %s: поля обновлены', $iblockId);
            return $ok;
        }

        $this->outIf(true, 'Инфоблок %s: поля совпадают', $iblockId);
        return true;
    }

    protected function saveProperty($iblockId, $fields) {
        $this->helper->Iblock()->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        $exists = $this->helper->Iblock()->getProperty($iblockId, $fields['CODE']);
        $exportExists = $this->helper->Iblock()->prepareExportProperty($exists);
        $fields = $this->helper->Iblock()->prepareExportProperty($fields);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $this->helper->Iblock()->addProperty($iblockId, $fields);
            $this->outSuccessIf($ok, 'Инфоблок %s: свойство %s добавлено', $iblockId, $fields['CODE']);
            return $ok;
        }


        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $this->helper->Iblock()->updatePropertyById($exists['ID'], $fields);
            $this->outSuccessIf($ok, 'Инфоблок %s: свойство %s обновлено', $iblockId, $fields['CODE']);
            return $ok;
        }

        $ok = ($this->testMode) ? true : $exists['ID'];
        $this->outIf($ok, 'Инфоблок %s: свойство %s совпадает', $iblockId, $fields['CODE']);
        return $exists['ID'];
    }

    protected function saveElementForm($iblockId, $elementForm) {
        $exists = $this->helper->AdminIblock()->exportElementForm($iblockId);
        if ($exists != $elementForm) {
            $ok = ($this->testMode) ? true : $this->helper->AdminIblock()->saveElementForm($iblockId, $elementForm);
            $this->outSuccessIf($ok, 'Инфоблок %s: форма редактирования сохранена', $iblockId);
        } else {
            $this->out('Инфоблок %s: форма редактирования cовпадает', $iblockId);
        }
    }


    protected function cleanProperties($iblockId, $skip = array()) {
        $olds = $this->helper->Iblock()->getProperties($iblockId);
        foreach ($olds as $old) {
            $uniq = $this->getUniqProp($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $this->helper->Iblock()->deletePropertyById($old['ID']);
                $this->outErrorIf($ok, 'Инфоблок %s: свойство %s удалено', $iblockId, $this->getTitleProp($old));
            }
        }
    }

    protected function cleanIblockTypes($skip = array()) {
        $olds = $this->helper->Iblock()->getIblockTypes();
        foreach ($olds as $old) {
            $uniq = $this->getUniqIblockType($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $this->helper->Iblock()->deleteIblockType($old['ID']);
                $this->outErrorIf($ok, 'Тип инфоблока %s: удален', $old['ID']);
            }
        }
    }

    protected function cleanIblocks($skip = array()) {
        $olds = $this->helper->Iblock()->getIblocks();
        foreach ($olds as $old) {
            $uniq = $this->getUniqIblock($old);
            if (!in_array($uniq, $skip)) {
                $ok = ($this->testMode) ? true : $this->helper->Iblock()->deleteIblock($old['ID']);
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
        $uniq = $this->getUniqIblock($iblock);
        if (!isset($this->cache[$uniq])) {
            $this->cache[$uniq] = $this->helper->Iblock()->getIblockId(
                $iblock['CODE'],
                $iblock['IBLOCK_TYPE_ID']
            );
        }

        return $this->cache[$uniq];

    }

}