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
        $result = HL\HighloadBlockTable::getList(
            array(
                'select' => array('ID'),
                'filter' => array('NAME' => $name),
            )
        );
        return $result->fetch();
    }

    public function getHlblockId($name) {
        $aItem = $this->getHlblock($name);
        return ($aItem && isset($aItem['ID'])) ? $aItem['ID'] : 0;
    }

    public function addHlblock($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('NAME', 'TABLE_NAME'));

        $fields['NAME'] = ucfirst($fields['NAME']);

        $result = HL\HighloadBlockTable::add($fields);

        if ($result->getId()) {
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
        $result = HL\HighloadBlockTable::update($hlblockId, $fields);
        if ($result->isSuccess()) {
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


}