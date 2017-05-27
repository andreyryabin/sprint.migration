<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;


class HlblockHelper extends Helper
{

    public function __construct() {
        Loader::includeModule('highloadblock');
    }

    public function getHlblock($name) {
        if (is_array($name)){
            $filter = $name;
        } elseif (is_numeric($name)){
            $filter = array('ID' => $name);
        } else {
            $filter = array('NAME' => $name);
        }

        $result = HL\HighloadBlockTable::getList(
            array(
                'select' => array('*'),
                'filter' => $filter,
            )
        );

        $hlblock = $result->fetch();
        if ($hlblock){
            $hlblock['LANG'] = $this->getHblockLangs($hlblock['ID']);
        }

        return $hlblock;
    }

    public function getHlblockId($name) {
        $aItem = $this->getHlblock($name);
        return ($aItem && isset($aItem['ID'])) ? $aItem['ID'] : 0;
    }

    public function addHlblock($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('NAME', 'TABLE_NAME'));

        $lang = array();
        if (isset($fields['LANG'])){
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

        $aItem = $this->getHlblock($fields['NAME']);
        if ($aItem) {
            return $aItem['ID'];
        }

        return $this->addHlblock($fields);
    }

    public function updateHlblock($hlblockId, $fields) {
        $lang = array();
        if (isset($fields['LANG'])){
            $lang = $fields['LANG'];
            unset($fields['LANG']);
        }

        $result = HL\HighloadBlockTable::update($hlblockId, $fields);
        if ($result->isSuccess()) {
            $this->replaceHblockLangs($hlblockId, $lang);
            return true;
        }

        $this->throwException(__METHOD__, implode(', ', $result->getErrors()));
    }

    public function updateHlblockIfExists($name, $fields) {
        $aItem = $this->getHlblock($name);
        if (!$aItem) {
            return false;
        }

        return $this->updateHlblock($aItem['ID'], $fields);
    }

    public function deleteHlblock($hlblockId) {
        $result = HL\HighloadBlockTable::delete($hlblockId);
        if ($result->isSuccess()) {
            return true;
        }

        $this->throwException(__METHOD__, implode(', ', $result->getErrors()));
    }

    public function deleteHlblockIfExists($name) {
        $aItem = $this->getHlblock($name);
        if (!$aItem) {
            return false;
        }

        return $this->deleteHlblock($aItem['ID']);
    }

    protected function getHblockLangs($hlblockId){
        $dbres = HL\HighloadBlockLangTable::getList(array(
            'filter' => array('ID' => $hlblockId)
        ));

        $result = array();
        while ($aItem = $dbres->fetch()){
            $result[$aItem['LID']] = array(
                'NAME' => $aItem['NAME']
            );
        }
        return $result;
    }

    protected function deleteHblockLangs($hlblockId){
        $res = HL\HighloadBlockLangTable::getList(array(
            'filter' => array('ID' => $hlblockId)
        ));

        while ($row = $res->fetch()){
            HL\HighloadBlockLangTable::delete($row['ID']);
        }
    }

    protected function addHblockLangs($hlblockId, $lang = array()){
        foreach ($lang as $lid => $item){
            if (!empty($item['NAME'])){
                HL\HighloadBlockLangTable::add(array(
                    'ID' => $hlblockId,
                    'LID' => $lid,
                    'NAME' => $item['NAME']
                ));
            }
        }
    }

    protected function replaceHblockLangs($hlblockId, $lang = array()){
        if (!empty($lang) && is_array($lang)){
            $this->deleteHblockLangs($hlblockId);
            $this->addHblockLangs($hlblockId, $lang);
        }
    }
}