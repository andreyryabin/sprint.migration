<?php

namespace Sprint\Migration\Helpers;

/**
 * Class IblockHelper
 * @package Sprint\Migration\Helpers
 * @help http://dev.1c-bitrix.ru/api_help/iblock/classes/ciblockproperty/getlist.php
 */
class IblockHelper
{

    public $lastError = '';

    public function addIblockTypeIfNotExists($fields) {
        $id = $fields['ID'];
        if (!$this->getIblockTypeId($id)) {
            $id = $this->addIblockType($fields);
        }
        return $id;
    }

    public function addIblockIfNotExists($fields) {
        $code = $fields['CODE'];
        $iblockId = $this->getIblockId($code);
        if ($iblockId <= 0) {
            $iblockId = $this->addIblock($fields);
        }
        return $iblockId;
    }

    public function deleteIblockIfExists($iblockCode){
        $iblockId = $this->getIblockId($iblockCode);
        return ($iblockId) ? \CIBlock::Delete($iblockId) : false;
    }

    public function addPropertyIfNotExists($iblockId, $fields) {
        $code = $fields['CODE'];
        $propId = $this->getPropertyId($iblockId, $code);
        if ($propId <= 0) {
            $propId = $this->addProperty($iblockId, $fields);
        }
        return $propId;
    }

    public function deleteProperty($iblockId, $propertyCode) {
        $this->lastError = '';

        $propId = $this->getPropertyId($iblockId, $propertyCode);
        if (!$propId) {
            return false;
        }

        $ib = new \CIBlockProperty;
        $ok = $ib->Delete($propId);

        $this->lastError = $ib->LAST_ERROR;

        return $ok;
    }

    public function updateProperty($iblockId, $propertyCode, $fields) {
        $this->lastError = '';

        $propId = $this->getPropertyId($iblockId, $propertyCode);
        if (!$propId) {
            return false;
        }

        $ib = new \CIBlockProperty();
        $ok = $ib->Update($propId, $fields);

        $this->lastError = $ib->LAST_ERROR;

        return $ok;
    }

    public function addSection($iblockId, $fields) {
        $this->lastError = '';

        $default = Array(
            "ACTIVE" => "Y",
            "IBLOCK_SECTION_ID" => false,
            "NAME" => 'section',
            "CODE" => '',
            "SORT" => 100,
            "PICTURE" => false,
            "DESCRIPTION" => '',
            "DESCRIPTION_TYPE" => 'text'
        );

        $fields = array_merge($default, $fields);
        $fields["IBLOCK_ID"] = $iblockId;

        $ib = new \CIBlockSection;
        $id = $ib->Add($fields);

        $this->lastError = $ib->LAST_ERROR;

        return $id;
    }

    public function addElement($iblockId, $fields, $props = array()) {
        $this->lastError = '';

        $default = array(
            "NAME" => "element",
            "IBLOCK_SECTION_ID" => false,
            "ACTIVE" => "Y",
            "PREVIEW_TEXT" => "",
            "DETAIL_TEXT" => "",
        );

        $fields = array_merge($default, $fields);
        $fields["IBLOCK_ID"] = $iblockId;

        if (!empty($props)) {
            $fields['PROPERTY_VALUES'] = $props;
        }

        $ib = new \CIBlockElement;
        $id = $ib->Add($fields);

        $this->lastError = $ib->LAST_ERROR;

        return $id;
    }

    public function getIblockId($code) {
        $aIblock = $this->getIblock($code);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }

    public function getIblock($code) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return \CIBlock::GetList(array('SORT' => 'ASC'), array('CHECK_PERMISSIONS' => 'N', 'CODE' => $code))->Fetch();
    }

    public function getIblocks() {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbResult = \CIBlock::GetList(array('SORT' => 'ASC'), array('CHECK_PERMISSIONS' => 'N'));
        $list = array();
        while ($aItem = $dbResult->Fetch()) {
            $list[] = $aItem;
        }
        return $list;
    }

    public function getIblockType($id) {
        return \CIBlockType::GetList(array('SORT' => 'ASC'), array('CHECK_PERMISSIONS' => 'N', '=ID' => $id))->Fetch();
    }

    public function getIblockTypeId($id) {
        $aIblock = $this->getIblockType($id);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }

    public function getPropertyId($iblockId, $code) {
        $aIblock = $this->getProperty($iblockId, $code);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }

    public function getProperty($iblockId, $code) {
        return \CIBlockProperty::GetList(array('SORT' => 'ASC'), array('IBLOCK_ID' => $iblockId, 'CODE' => $code, 'CHECK_PERMISSIONS' => 'N'))->Fetch();
    }

    protected function addProperty($iblockId, $fields) {
        $this->lastError = '';

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

        $fields = array_merge($default, $fields);
        if (isset($fields['VALUES'])) {
            $fields['PROPERTY_TYPE'] = 'L';
        }

        $ib = new \CIBlockProperty;
        $id = $ib->Add($fields);

        $this->lastError = $ib->LAST_ERROR;

        return $id;
    }

    protected function addIblockType($fields) {

        $this->lastError = '';

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

        $fields = array_merge($default, $fields);

        $ib = new \CIBlockType;
        $res = $ib->Add($fields);

        $this->lastError = $ib->LAST_ERROR;

        return ($res) ? $fields['ID'] : 0;
    }

    protected function addIblock($fields) {
        $this->lastError = '';

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

        $fields = array_merge($default, $fields);

        $ib = new \CIBlock;
        $id = $ib->Add($fields);

        $this->lastError = $ib->LAST_ERROR;
        return $id;
    }

    public function mergeIblockFields($iblockId, $fields){
        $default = \CIBlock::GetFields($iblockId);
        $fields = $this->arraySoftMerge($default, $fields);
        \CIBlock::SetFields($iblockId, $fields);
    }

        protected function arraySoftMerge($default, $fields){
        foreach ($default as $key => $val){
            if (isset($fields[$key])) {
                if (is_array($val) && is_array($fields[$key])){
                    $default[$key] = $this->arraySoftMerge($val, $fields[$key]);
                } else {
                    $default[$key] = $fields[$key];
                }
            }
            unset($fields[$key]);
        }

        foreach ($fields as $key=>$val){
            $default[$key] = $val;
        }

        return $default;
    }

    public function getLastError($stripTags=true){
        return ($stripTags) ? strip_tags($this->lastError) : $this->lastError;
    }
}