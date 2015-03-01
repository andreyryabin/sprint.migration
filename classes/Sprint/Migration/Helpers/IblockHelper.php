<?php

namespace Sprint\Migration\Helpers;

/**
 * PROPERTY_TYPE:USER_TYPE
 * S:DateTime - Дата/Время
 * S:ElementXmlID - Привязка к элементам по XML_ID
 * S:FileMan - Привязка к файлу (на сервере)
 * S:HTML - HTML/текст
 * E:EList - Привязка к элементам в виде списка
 * N:Sequence - Счетчик
 * E:EAutocomplete - Привязка к элементам с автозаполнением
 * E:SKU - Привязка к товарам (SKU)
 * S:UserID - Привязка к пользователю
 * S:map_google - Привязка к карте Google Maps
 * S:map_yandex - Привязка к Яндекс.Карте
 * S:video - Видео
 * S:TopicID - Привязка к теме форума
 */
class IblockHelper
{

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

    public function addPropertyIfNotExists($iblockId, $fields) {
        $code = $fields['CODE'];
        $propId = $this->getPropertyId($iblockId, $code);
        if ($propId <= 0) {
            $propId = $this->addProperty($iblockId, $fields);
        }
        return $propId;
    }

    public function deleteProperty($iblockId, $propertyCode) {
        $propId = $this->getPropertyId($iblockId, $propertyCode);
        if (!$propId) {
            return false;
        }

        $ib = new \CIBlockProperty;
        $ok = $ib->Delete($propId);

        return $ok;
    }

    public function updateProperty($iblockId, $propertyCode, $fields) {
        $propId = $this->getPropertyId($iblockId, $propertyCode);
        if (!$propId) {
            return false;
        }

        $oIblockProperty = new \CIBlockProperty();
        $ok = $oIblockProperty->Update($propId, $fields);

        return $ok;
    }

    public function addSection($iblockId, $fields) {
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

        $section = new \CIBlockSection;
        $id = $section->Add($fields);

        return $id;
    }

    public function addElement($iblockId, $fields, $props = array()) {
        $default = array(
            "NAME" => "Элемент",
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

        $element = new \CIBlockElement;
        $id = $element->Add($fields);

        return $id;
    }

    public function getIblockId($code) {
        $aIblock = $this->getIblock($code);
        return ($aIblock && isset($aIblock['ID'])) ? $aIblock['ID'] : 0;
    }

    public function getIblock($code) {
        return \CIBlock::GetList(array('SORT' => 'ASC'), array('CHECK_PERMISSIONS' => 'N', 'CODE' => $code))->Fetch();
    }

    public function getIblocks() {
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
        return $id;
    }

    protected function addIblockType($fields) {
        $default = Array(
            'ID' => '',
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => Array(
                'ru' => Array(
                    'NAME' => 'Каталог',
                    'SECTION_NAME' => 'Разделы',
                    'ELEMENT_NAME' => 'Элементы'
                ),
                'en' => Array(
                    'NAME' => 'Catalog',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Products'
                ),
            )
        );

        $fields = array_merge($default, $fields);

        $ib = new \CIBlockType;
        $res = $ib->Add($fields);
        return ($res) ? $fields['ID'] : 0;
    }

    protected function addIblock($fields) {
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
}