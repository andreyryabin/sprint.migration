<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class IblockHelper extends Helper
{


    public function getIblockTypes() {
        $dbResult = \CIBlockType::GetList(array(
            'SORT' => 'ASC'
        ), array(
            'CHECK_PERMISSIONS' => 'N'
        ));
        $list = array();
        while ($aItem = $dbResult->Fetch()) {
            $list[] = $aItem;
        }
        return $list;
    }

    /** @compatibility */
    public function getIblockType($iblockTypeId) {
        return \CIBlockType::GetList(array(
            'SORT' => 'ASC'
        ), array(
            'CHECK_PERMISSIONS' => 'N',
            '=ID' => $iblockTypeId
        ))->Fetch();
    }

    /** @compatibility */
    public function getIblockTypeId($iblockTypeId) {
        $aIblock = $this->getIblockType($iblockTypeId);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }

    /** @compatibility */
    public function addIblockTypeIfNotExists($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('ID'));

        $id = $fields['ID'];

        if ($this->getIblockTypeId($id)) {
            return $id;
        }

        $default = Array(
            'ID' => '',
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => Array(
                'ru' => Array(
                    'NAME' => 'Catalog',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements'
                ),
                'en' => Array(
                    'NAME' => 'Catalog',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements'
                ),
            )
        );

        $fields = array_replace_recursive($default, $fields);

        $ib = new \CIBlockType;
        if ($ib->Add($fields)) {
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }
    public function deleteIblockTypeIfExists($iblockTypeId) {
        $iblockTypeId = $this->getIblockTypeId($iblockTypeId);

        if (!$iblockTypeId) {
            return false;
        }

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        if (\CIBlockType::Delete($iblockTypeId)) {
            return true;
        }

        $this->throwException(__METHOD__, 'Could not delete iblock type %s', $iblockTypeId);
    }


    /** @compatibility */
    public function getIblock($code, $iblockTypeId = '') {
        $filter = array('CHECK_PERMISSIONS' => 'N', '=CODE' => $code);
        if (!empty($iblockTypeId)) {
            $filter['=TYPE'] = $iblockTypeId;
        }
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return \CIBlock::GetList(array('SORT' => 'ASC'), $filter)->Fetch();
    }

    /** @compatibility */
    public function getIblockId($code, $iblockTypeId = '') {
        $aIblock = $this->getIblock($code, $iblockTypeId);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }

    /** @compatibility */
    public function getIblocks() {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbResult = \CIBlock::GetList(array('SORT' => 'ASC'), array('CHECK_PERMISSIONS' => 'N'));
        $list = array();
        while ($aItem = $dbResult->Fetch()) {
            $list[] = $aItem;
        }
        return $list;
    }

    /** @compatibility */
    public function addIblockIfNotExists($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        if (!empty($fields['IBLOCK_TYPE_ID'])) {
            $iblockId = $this->getIblockId($fields['CODE'], $fields['IBLOCK_TYPE_ID']);
        } else {
            $iblockId = $this->getIblockId($fields['CODE']);
        }

        if ($iblockId) {
            return $iblockId;
        }

        $default = array(
            'ACTIVE' => 'Y',
            'NAME' => '',
            'CODE' => '',
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '',
            'SECTION_PAGE_URL' => '',
            'IBLOCK_TYPE_ID' => 'main',
            'SITE_ID' => array('s1'),
            'SORT' => 500,
            'GROUP_ID' => array('2' => 'R'),
            'VERSION' => 2,
            'BIZPROC' => 'N',
            'WORKFLOW' => 'N',
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N'
        );

        $fields = array_replace_recursive($default, $fields);

        $ib = new \CIBlock;
        $iblockId = $ib->Add($fields);

        if ($iblockId) {
            return $iblockId;
        }
        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /** @compatibility */
    public function deleteIblockIfExists($iblockCode, $iblockTypeId = '') {
        $iblockId = $this->getIblockId($iblockCode, $iblockTypeId);
        if (!$iblockId) {
            return false;
        }

        if (\CIBlock::Delete($iblockId)) {
            return true;
        }

        $this->throwException(__METHOD__, 'Could not delete iblock %s', $iblockCode);
    }

    /** @compatibility */
    public function mergeIblockFields($iblockId, $fields) {
        $default = \CIBlock::GetFields($iblockId);
        $fields = array_replace_recursive($default, $fields);
        \CIBlock::SetFields($iblockId, $fields);
    }

    /** @compatibility */
    public function getProperty($iblockId, $code) {
        /* do not use =CODE in filter */
        return \CIBlockProperty::GetList(array('SORT' => 'ASC'), array('IBLOCK_ID' => $iblockId, 'CODE' => $code, 'CHECK_PERMISSIONS' => 'N'))->Fetch();
    }


    /** @compatibility */
    public function getPropertyId($iblockId, $code) {
        $aIblock = $this->getProperty($iblockId, $code);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }

    /** @compatibility */
    public function addPropertyIfNotExists($iblockId, $fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        $propId = $this->getPropertyId($iblockId, $fields['CODE']);

        if ($propId) {
            return $propId;
        }

        $default = array(
            'IBLOCK_ID' => $iblockId,
            'NAME' => '',
            'ACTIVE' => 'Y',
            'SORT' => '500',
            'CODE' => '',
            'PROPERTY_TYPE' => 'S',
            'ROW_COUNT' => '1',
            'COL_COUNT' => '30',
            'LIST_TYPE' => 'L',
            'MULTIPLE' => 'N',
            'USER_TYPE' => '',
            'IS_REQUIRED' => 'N',
            'FILTRABLE' => 'Y',
            'LINK_IBLOCK_ID' => 0
        );

        $fields = array_replace_recursive($default, $fields);
        if (isset($fields['VALUES'])) {
            $fields['PROPERTY_TYPE'] = 'L';
        }

        $ib = new \CIBlockProperty;
        $propId = $ib->Add($fields);

        if ($propId) {
            return $propId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);

    }

    /** @compatibility */
    public function deletePropertyIfExists($iblockId, $propertyCode) {
        $propId = $this->getPropertyId($iblockId, $propertyCode);
        if (!$propId) {
            return false;
        }

        $ib = new \CIBlockProperty;
        if ($ib->Delete($propId)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);

    }

    /** @compatibility */
    public function updatePropertyIfExists($iblockId, $propertyCode, $fields) {
        $propId = $this->getPropertyId($iblockId, $propertyCode);
        if (!$propId) {
            return false;
        }

        $ib = new \CIBlockProperty();
        if ($ib->Update($propId, $fields)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);

    }

    /** @compatibility */
    public function addElement($iblockId, $fields = array(), $props = array()){
        $fields['IBLOCK_ID'] = $iblockId;

        $default = array(
            "NAME" => "element",
            "IBLOCK_SECTION_ID" => false,
            "ACTIVE" => "Y",
            "PREVIEW_TEXT" => "",
            "DETAIL_TEXT" => "",
        );

        $fields = array_replace_recursive($default, $fields);

        if (!empty($props)) {
            $fields['PROPERTY_VALUES'] = $props;
        }

        $ib = new \CIBlockElement;
        $id = $ib->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /** @compatibility */
    public function addElementIfNotExists($filter, $fields, $props = array()){

        /** compatibility */
        if (is_numeric($filter)){
            $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));
            $filter = array(
                'IBLOCK_ID' => $filter,
                '=CODE' => $fields['CODE']
            );
        }

        $aItem = $this->getElement($filter);
        if ($aItem){
            return $aItem['ID'];
        }

        $fields['IBLOCK_ID'] = $filter['IBLOCK_ID'];
        return $this->addElement($fields['IBLOCK_ID'],$fields, $props);

    }
    public function updateElementIfExists($filter,$fields = array(), $props = array()){
        $aItem = $this->getElement($filter);
        if (!$aItem) {
            return false;
        }

        if (!empty($fields)){
            $ib = new \CIBlockElement;
            if ($ib->Update($aItem['ID'], $fields)) {
                return true;
            }

            $this->throwException(__METHOD__, $ib->LAST_ERROR);
        }

        if (!empty($props)){
            \CIBlockElement::SetPropertyValuesEx($aItem['ID'], $filter['IBLOCK_ID'], $props);
            return true;
        }

        return false;
    }

    /** @compatibility */
    public function deleteElementIfExists($filter, $code= false){

        /** compatibility */
        if (is_numeric($filter) && $code){
            $filter = array(
                'IBLOCK_ID' => $filter,
                '=CODE' => $code
            );
        }

        $aItem = $this->getElement($filter);

        if (!$aItem) {
            return false;
        }

        $ib = new \CIBlockElement;
        if ($ib->Delete($aItem['ID'])) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }
    public function getElement($filter) {
        $this->checkRequiredKeys(__METHOD__, $filter, array('IBLOCK_ID'));

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return \CIBlockElement::GetList(array(
            'SORT' => 'ASC'
        ), $filter, false, array(
            'nTopCount' => 1
        ), array(
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
        ))->Fetch();
    }
    /** @compatibility */
    public function addSection($iblockId, $fields = array()){
        $fields['IBLOCK_ID'] = $iblockId;

        $default = array(
            "ACTIVE" => "Y",
            "IBLOCK_SECTION_ID" => false,
            "NAME" => 'section',
            "CODE" => '',
            "SORT" => 100,
            "PICTURE" => false,
            "DESCRIPTION" => '',
            "DESCRIPTION_TYPE" => 'text'
        );

        $fields = array_replace_recursive($default, $fields);

        $ib = new \CIBlockSection;
        $id = $ib->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }
    public function addSectionIfNotExists($filter,$fields){
        $aItem = $this->getSection($filter);
        if ($aItem) {
            return $aItem['ID'];
        }

        $fields['IBLOCK_ID'] = $filter['IBLOCK_ID'];
        return $this->addSection($fields);

    }
    public function updateSectionIfExists($filter,$fields){
        $aItem = $this->getSection($filter);
        if (!$aItem) {
            return false;
        }

        $ib = new \CIBlockSection;
        $id = $ib->Update($aItem['ID'], $fields);
        if ($id){
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }
    public function deleteSectionIfExists($filter) {
        $aItem = $this->getSection($filter);
        if (!$aItem) {
            return false;
        }

        $ib = new \CIBlockSection;
        if ($ib->Delete($aItem['ID'])) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }
    public function getSection($filter) {
        $this->checkRequiredKeys(__METHOD__, $filter, array('IBLOCK_ID'));

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return \CIBlockSection::GetList(array(
            'SORT' => 'ASC'
        ), $filter, false, array(
            'nTopCount' => 1
        ), array(
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
        ))->Fetch();
    }

    /* @deprecated */
    public function deleteProperty($iblockId, $propertyCode) {
        return $this->deletePropertyIfExists($iblockId, $propertyCode);
    }

    /* @deprecated */
    public function updateProperty($iblockId, $propertyCode, $fields) {
        return $this->updatePropertyIfExists($iblockId, $propertyCode, $fields);
    }

}