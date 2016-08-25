<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class IblockElementHelper extends Helper
{

    public function addElementIfNotExists($filter,$fields,$props = array()){
        $this->checkRequiredKeys(__METHOD__, $filter, array('IBLOCK_ID'));
        $elementId = $this->getElementId($filter);
        if ($elementId) {
            return $elementId;
        }

        $fields['IBLOCK_ID'] = $filter['IBLOCK_ID'];
        return $this->addElement($fields, $props);

    }

    public function updateElementIfExists($filter,$fields){
        $this->checkRequiredKeys(__METHOD__, $filter, array('IBLOCK_ID'));
        $elementId = $this->getElementId($filter);

        if (!$elementId) {
            return false;
        }

        $ib = new \CIBlockElement;
        $id = $ib->Update($elementId, $fields);

        if ($id){
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function deleteElementIfExists($filter) {
        $this->checkRequiredKeys(__METHOD__, $filter, array('IBLOCK_ID'));
        $elementId = $this->getElementId($filter);

        if (!$elementId) {
            return false;
        }

        $ib = new \CIBlockElement;
        if ($ib->Delete($elementId)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    public function addElement($fields, $props = array()){
        $this->checkRequiredKeys(__METHOD__, $fields, array('IBLOCK_ID'));

        $default = array(
            "IBLOCK_ID" => '',
            "NAME" => "element",
            "IBLOCK_SECTION_ID" => false,
            "ACTIVE" => "Y",
            "PREVIEW_TEXT" => "",
            "DETAIL_TEXT" => "",
        );

        $fields = array_replace_recursive($default, $fields);
        if (!empty($props)){
            $fields['PROPERTY_VALUES'] = $props;
        }

        $ib = new \CIBlockElement;
        $id = $ib->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);

    }

    public function getElementId($filter) {
        $this->checkRequiredKeys(__METHOD__, $filter, array('IBLOCK_ID'));

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $aItem = \CIBlockElement::GetList(array(
            'SORT' => 'ASC'
        ), $filter, false, array(
            'nTopCount' => 1
        ), array(
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
        ))->Fetch();

        return ($aItem && $aItem['ID']) ? $aItem['ID'] : false;
    }


}