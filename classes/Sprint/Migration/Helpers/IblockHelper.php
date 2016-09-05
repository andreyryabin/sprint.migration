<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class IblockHelper extends Helper
{


    public function getIblockTypes($filter = array()) {
        $filter['CHECK_PERMISSIONS'] = 'N';
        $dbResult = \CIBlockType::GetList(array('SORT' => 'ASC'), $filter);

        $list = array();
        while ($aItem = $dbResult->Fetch()) {
            $list[] = $aItem;
        }
        return $list;
    }

    public function getIblockType($filter) {
        /** @compatibility */
        if (!is_array($filter)){
            $filter = array(
                '=ID' => $filter
            );
        }

        $filter['CHECK_PERMISSIONS'] = 'N';

        return \CIBlockType::GetList(array('SORT' => 'ASC'), array($filter))->Fetch();
    }

    
    public function getIblockTypeId($filter) {
        /** @compatibility */
        $aIblock = $this->getIblockType($filter);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }

    
    public function addIblockTypeIfNotExists($fields) {
        /** @compatibility */
        $this->checkRequiredKeys(__METHOD__, $fields, array('ID'));

        $id = $fields['ID'];

        if ($this->getIblockType($id)) {
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

    public function deleteIblockTypeIfExists($filter) {
        $aIblockType = $this->getIblockType($filter);
        if (!$aIblockType) {
            return false;
        }

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        if (\CIBlockType::Delete($aIblockType['ID'])) {
            return true;
        }

        $this->throwException(__METHOD__, 'Could not delete iblock type %s', $aIblockType['ID']);
    }



    public function getIblock($filter, $iblockTypeId = false) {
        /** @compatibility */
        if (!is_array($filter)){
            $filter = array(
                '=CODE' => $filter
            );
        }

        if ($iblockTypeId){
            $filter['=TYPE'] = $iblockTypeId;
        }

        $filter['CHECK_PERMISSIONS'] = 'N';
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return \CIBlock::GetList(array('SORT' => 'ASC'), $filter)->Fetch();
    }

    
    public function getIblockId($filter, $iblockTypeId = false) {
        /** @compatibility */
        $aIblock = $this->getIblock($filter, $iblockTypeId);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }

    
    public function getIblocks() {
        /** @compatibility */
        
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbResult = \CIBlock::GetList(array('SORT' => 'ASC'), array('CHECK_PERMISSIONS' => 'N'));
        $list = array();
        while ($aItem = $dbResult->Fetch()) {
            $list[] = $aItem;
        }
        return $list;
    }

    
    public function addIblockIfNotExists($fields) {
        /** @compatibility */
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        $iblockCode = $fields['CODE'];
        $iblockTypeId = !empty($fields['IBLOCK_TYPE_ID']) ? $fields['IBLOCK_TYPE_ID'] : false;

        $aIblock = $this->getIblock($iblockCode, $iblockTypeId);
        if ($aIblock) {
            return $aIblock['ID'];
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

    
    public function deleteIblockIfExists($filter, $iblockTypeId = false) {
        /** @compatibility */

        $aIblock = $this->getIblock($filter, $iblockTypeId);
        if (!$aIblock) {
            return false;
        }

        if (\CIBlock::Delete($aIblock['ID'])) {
            return true;
        }

        $this->throwException(__METHOD__, 'Could not delete iblock %s', $aIblock['ID']);
    }

    public function updateIblockFields($iblockId, $fields = array()) {
        self::mergeIblockFields($iblockId, $fields);
    }

    public function getProperty($iblockId, $filter) {
        /** @compatibility */
        if (!is_array($filter)){
            $filter = array(
                'CODE' => $filter,
            );
        }

        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';
        return \CIBlockProperty::GetList(array('SORT' => 'ASC'), $filter)->Fetch();
    }


    
    public function getPropertyId($iblockId, $filter) {
        /** @compatibility */
        $aIblock = $this->getProperty($iblockId, $filter);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }

    
    public function addPropertyIfNotExists($iblockId, $fields) {
        /** @compatibility */
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        $aProperty = $this->getProperty($iblockId, $fields['CODE']);

        if ($aProperty) {
            return $aProperty['ID'];
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

        if (!empty($fields['VALUES'])) {
            $fields['PROPERTY_TYPE'] = 'L';
        }

        if (!empty($fields['LINK_IBLOCK_ID'])){
            $fields['PROPERTY_TYPE'] = 'E';
        }

        $fields = array_replace_recursive($default, $fields);

        $ib = new \CIBlockProperty;
        $propertyId = $ib->Add($fields);

        if ($propertyId) {
            return $propertyId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);

    }

    
    public function deletePropertyIfExists($iblockId, $filter) {
        /** @compatibility */
        $aProperty = $this->getProperty($iblockId, $filter);
        if (!$aProperty) {
            return false;
        }

        $ib = new \CIBlockProperty;
        if ($ib->Delete($aProperty['ID'])) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);

    }

    
    public function updatePropertyIfExists($iblockId, $filter, $fields) {
        /** @compatibility */
        $aProperty = $this->getProperty($iblockId, $filter);
        if (!$aProperty) {
            return false;
        }

        $fields['IBLOCK_ID'] = $iblockId;

        $ib = new \CIBlockProperty();
        if ($ib->Update($aProperty['ID'], $fields)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);

    }

    
    public function addElement($iblockId, $fields = array(), $props = array()){
        /** @compatibility */
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

    
    public function addElementIfNotExists($filter, $fields, $props = array()){
        /** @compatibility */
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

    
    public function deleteElementIfExists($filter, $code= false){
        /** @compatibility */
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
    
    public function addSection($iblockId, $fields = array()){
        /** @compatibility */
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

    /** @deprecated  */
    public function mergeIblockFields($iblockId, $fields) {
        $default = \CIBlock::GetFields($iblockId);
        $fields = array_replace_recursive($default, $fields);
        \CIBlock::SetFields($iblockId, $fields);
    }
}