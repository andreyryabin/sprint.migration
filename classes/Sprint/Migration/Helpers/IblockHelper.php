<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class IblockHelper extends Helper
{

    public function getIblocks() {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbResult = \CIBlock::GetList(array('SORT' => 'ASC'), array('CHECK_PERMISSIONS' => 'N'));
        $list = array();
        while ($aItem = $dbResult->Fetch()) {
            $list[] = $aItem;
        }
        return $list;
    }
    public function getIblock($code, $iblockTypeId = '') {
        $filter = array('CHECK_PERMISSIONS' => 'N', '=CODE' => $code);
        if (!empty($iblockTypeId)){
            $filter['=TYPE'] = $iblockTypeId;
        }
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return \CIBlock::GetList(array('SORT' => 'ASC'), $filter)->Fetch();
    }
    public function getIblockId($code, $iblockTypeId = '') {
        $aIblock = $this->getIblock($code, $iblockTypeId);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }
    public function addIblockIfNotExists($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));

        if (!empty($fields['IBLOCK_TYPE_ID'])){
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
    public function mergeIblockFields($iblockId, $fields) {
        $default = \CIBlock::GetFields($iblockId);
        $fields = array_replace_recursive($default, $fields);
        \CIBlock::SetFields($iblockId, $fields);
    }

    public function getIblockTypes() {
        $dbResult = \CIBlockType::GetList(array('SORT' => 'ASC'), array('CHECK_PERMISSIONS' => 'N'));
        $list = array();
        while ($aItem = $dbResult->Fetch()) {
            $list[] = $aItem;
        }
        return $list;
    }
    public function getIblockType($iblockTypeId) {
        return \CIBlockType::GetList(array('SORT' => 'ASC'), array('CHECK_PERMISSIONS' => 'N', '=ID' => $iblockTypeId))->Fetch();
    }
    public function getIblockTypeId($iblockTypeId) {
        $aIblock = $this->getIblockType($iblockTypeId);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }
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
    public function deleteIblockTypeIfExists($iblockTypeId){
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


    public function getProperty($iblockId, $code) {
        /* do not use =CODE in filter */
        return \CIBlockProperty::GetList(array('SORT' => 'ASC'), array('IBLOCK_ID' => $iblockId, 'CODE' => $code, 'CHECK_PERMISSIONS' => 'N'))->Fetch();
    }
    public function getPropertyId($iblockId, $code) {
        $aIblock = $this->getProperty($iblockId, $code);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }
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



    /** @deprecated */
    public function addElementIfNotExists($iblockId, $fields, $props = array()) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('CODE'));
        $fields["IBLOCK_ID"] = $iblockId;

        $helper = new IblockElementHelper();
        return $helper->addElementIfNotExists(array(
            'IBLOCK_ID' => $iblockId,
            '=CODE' => $fields['CODE']
        ), $fields, $props);
    }
    /** @deprecated */
    public function addElement($iblockId, $fields = array(), $props = array()) {
        $fields["IBLOCK_ID"] = $iblockId;
        $helper = new IblockElementHelper();
        return $helper->addElement($fields, $props);
    }
    /** @deprecated */
    public function deleteElementIfExists($iblockId, $code) {
        $helper = new IblockElementHelper();
        return $helper->deleteElementIfExists(array(
            'IBLOCK_ID' => $iblockId,
            '=CODE' => $code
        ));
    }
    /** @deprecated */
    public function addSection($iblockId, $fields = array()) {
        $fields["IBLOCK_ID"] = $iblockId;
        $helper = new IblockSectionHelper();
        return $helper->addSection($fields);
    }
    /* @deprecated */
    public function updateProperty($iblockId, $propertyCode, $fields) {
        return $this->updatePropertyIfExists($iblockId, $propertyCode, $fields);
    }
    /* @deprecated */
    public function deleteProperty($iblockId, $propertyCode) {
        return $this->deletePropertyIfExists($iblockId, $propertyCode);
    }

}