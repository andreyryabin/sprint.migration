<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

use Bitrix\Highloadblock as HL;


class HlblockHelper extends Helper
{

    public function __construct() {
        $this->checkModules(array('highloadblock'));
    }

    public function getHlblocks($filter = array()) {
        $dbres = HL\HighloadBlockTable::getList(
            array(
                'select' => array('*'),
                'filter' => $filter,
            )
        );

        $result = [];
        while ($hlblock = $dbres->fetch()) {
            $hlblock['LANG'] = $this->getHblockLangs($hlblock['ID']);
            $result[] = $hlblock;
        }

        return $result;
    }

    public function exportHlblocks($filter = array()) {
        $items = $this->getHlblocks($filter);

        $export = array();
        foreach ($items as $item) {
            $export[] = $this->prepareExportHlblock($item);
        }

        return $export;
    }

    public function getFields($hlblockName) {
        $hlblockId = is_numeric($hlblockName) ? $hlblockName : $this->getHlblockId($hlblockName);

        $entityHelper = new UserTypeEntityHelper();
        $entityHelper->setTestMode($this->testMode);
        return $entityHelper->getUserTypeEntities('HLBLOCK_' . $hlblockId);
    }

    public function saveField($hlblockName, $field = array()) {
        $hlblockId = is_numeric($hlblockName) ? $hlblockName : $this->getHlblockId($hlblockName);
        $field['ENTITY_ID'] = 'HLBLOCK_' . $hlblockId;

        $entityHelper = new UserTypeEntityHelper();
        $entityHelper->setTestMode($this->testMode);
        return $entityHelper->saveUserTypeEntity($field);
    }

    public function deleteField($hlblockName, $fieldName) {
        $hlblockId = is_numeric($hlblockName) ? $hlblockName : $this->getHlblockId($hlblockName);

        $entityHelper = new UserTypeEntityHelper();
        $entityHelper->setTestMode($this->testMode);
        return $entityHelper->deleteUserTypeEntity('HLBLOCK_' . $hlblockId, $fieldName);
    }

    public function exportFields($hlblockName) {
        $fields = $this->getFields($hlblockName);
        $export = array();
        foreach ($fields as $field) {
            $export[] = $this->prepareExportHlblockField($field);
        }

        return $export;
    }

    public function exportHlblock($hlblockName) {
        return $this->prepareExportHlblock(
            $this->getHlblock($hlblockName)
        );
    }

    protected function prepareExportHlblockField($item) {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['ENTITY_ID']);

        return $item;
    }

    protected function prepareExportHlblock($item) {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);

        return $item;
    }

    public function getHlblock($hlblockName) {
        if (is_array($hlblockName)) {
            $filter = $hlblockName;
        } elseif (is_numeric($hlblockName)) {
            $filter = array('ID' => $hlblockName);
        } else {
            $filter = array('NAME' => $hlblockName);
        }

        $result = HL\HighloadBlockTable::getList(
            array(
                'select' => array('*'),
                'filter' => $filter,
            )
        );

        $hlblock = $result->fetch();
        if ($hlblock) {
            $hlblock['LANG'] = $this->getHblockLangs($hlblock['ID']);
        }

        return $hlblock;
    }

    public function getHlblockIfExists($hlblockName) {
        $item = $this->getHlblock($hlblockName);
        if ($item && isset($item['ID'])) {
            return $item;
        }

        $this->throwException(__METHOD__, "hlblock not found");
    }

    public function getHlblockIdIfExists($hlblockName) {
        $item = $this->getHlblock($hlblockName);
        if ($item && isset($item['ID'])) {
            return $item['ID'];
        }

        $this->throwException(__METHOD__, "hlblock id not found");
    }

    public function getHlblockId($hlblockName) {
        $item = $this->getHlblock($hlblockName);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    public function addHlblock($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('NAME', 'TABLE_NAME'));

        $lang = array();
        if (isset($fields['LANG'])) {
            $lang = $fields['LANG'];
            unset($fields['LANG']);
        }

        $fields['NAME'] = ucfirst($fields['NAME']);

        $result = HL\HighloadBlockTable::add($fields);
        if ($result->isSuccess()) {
            $this->replaceHblockLangs($result->getId(), $lang);
            return $result->getId();
        }

        $this->throwException(__METHOD__, implode(', ', $result->getErrors()));
    }

    public function addHlblockIfNotExists($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('NAME'));

        $item = $this->getHlblock($fields['NAME']);
        if ($item) {
            return $item['ID'];
        }

        return $this->addHlblock($fields);
    }

    public function updateHlblock($hlblockId, $fields) {
        $lang = array();
        if (isset($fields['LANG'])) {
            $lang = $fields['LANG'];
            unset($fields['LANG']);
        }

        $result = HL\HighloadBlockTable::update($hlblockId, $fields);
        if ($result->isSuccess()) {
            $this->replaceHblockLangs($hlblockId, $lang);
            return $hlblockId;
        }

        $this->throwException(__METHOD__, implode(', ', $result->getErrors()));
    }

    public function updateHlblockIfExists($hlblockName, $fields) {
        $item = $this->getHlblock($hlblockName);
        if (!$item) {
            return false;
        }

        return $this->updateHlblock($item['ID'], $fields);
    }

    public function deleteHlblock($hlblockId) {
        $result = HL\HighloadBlockTable::delete($hlblockId);
        if ($result->isSuccess()) {
            return true;
        }

        $this->throwException(__METHOD__, implode(', ', $result->getErrors()));
    }

    public function deleteHlblockIfExists($hlblockName) {
        $item = $this->getHlblock($hlblockName);
        if (!$item) {
            return false;
        }

        return $this->deleteHlblock($item['ID']);
    }

    protected function getHblockLangs($hlblockId) {
        $result = array();

        if (!class_exists('Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $result;
        }

        $dbres = HL\HighloadBlockLangTable::getList(array(
            'filter' => array('ID' => $hlblockId)
        ));


        while ($item = $dbres->fetch()) {
            $result[$item['LID']] = array(
                'NAME' => $item['NAME']
            );
        }

        return $result;
    }

    protected function deleteHblockLangs($hlblockId) {
        $del = 0;

        if (!class_exists('Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $del;
        }

        $res = HL\HighloadBlockLangTable::getList(array(
            'filter' => array('ID' => $hlblockId)
        ));


        while ($row = $res->fetch()) {
            HL\HighloadBlockLangTable::delete($row['ID']);
            $del++;
        }

        return $del;
    }

    protected function addHblockLangs($hlblockId, $lang = array()) {
        $add = 0;

        if (!class_exists('Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $add;
        }

        foreach ($lang as $lid => $item) {
            if (!empty($item['NAME'])) {
                HL\HighloadBlockLangTable::add(array(
                    'ID' => $hlblockId,
                    'LID' => $lid,
                    'NAME' => $item['NAME']
                ));

                $add++;
            }
        }

        return $add;
    }

    protected function replaceHblockLangs($hlblockId, $lang = array()) {
        if (!empty($lang) && is_array($lang)) {
            $this->deleteHblockLangs($hlblockId);
            $this->addHblockLangs($hlblockId, $lang);
        }
    }


    //version 2


    public function saveHlblock($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('NAME'));

        $exists = $this->getHlblock($fields['NAME']);
        $exportExists = $this->prepareExportHlblock($exists);
        $fields = $this->prepareExportHlblock($fields);

        if (empty($exists)) {
            $ok = ($this->testMode) ? true : $this->addHlblock($fields);
            $this->outNoticeIf($ok, 'Highload-блок %s: добавлен', $fields['NAME']);
            return $ok;
        }

        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $this->updateHlblock($exists['ID'], $fields);
            $this->outNoticeIf($ok, 'Highload-блок %s: обновлен', $fields['NAME']);
            return $ok;
        }


        $ok = ($this->testMode) ? true : $exists['ID'];
        $this->outIf($ok, 'Highload-блок %s: совпадает', $fields['NAME']);
        return $exists['ID'];
    }

}