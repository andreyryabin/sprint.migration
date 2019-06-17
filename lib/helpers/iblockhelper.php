<?php

namespace Sprint\Migration\Helpers;

use CIBlock;
use CIBlockElement;
use CIBlockProperty;
use CIBlockPropertyEnum;
use CIBlockSection;
use CIBlockType;
use CLanguage;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;

class IblockHelper extends Helper
{

    public function __construct()
    {
        $this->checkModules(['iblock']);
    }

    /**
     * Получает тип инфоблока, бросает исключение если его не существует
     * @param $typeId
     * @throws HelperException
     * @return array
     */
    public function getIblockTypeIfExists($typeId)
    {
        $item = $this->getIblockType($typeId);
        if ($item && isset($item['ID'])) {
            return $item;
        }

        $this->throwException(__METHOD__, "iblock type not found");
    }

    /**
     * Получает id типа инфоблока, бросает исключение если его не существует
     * @param $typeId
     * @throws HelperException
     * @return mixed
     */
    public function getIblockTypeIdIfExists($typeId)
    {
        $item = $this->getIblockType($typeId);
        if ($item && isset($item['ID'])) {
            return $item['ID'];
        }

        $this->throwException(__METHOD__, "iblock type id not found");
    }

    /**
     * Получает инфоблок, бросает исключение если его не существует
     * @param $code string|array - код или фильтр
     * @param string $typeId
     * @throws HelperException
     * @return mixed
     */
    public function getIblockIfExists($code, $typeId = '')
    {
        $item = $this->getIblock($code, $typeId);
        if ($item && isset($item['ID'])) {
            return $item;
        }

        $this->throwException(__METHOD__, "iblock not found");
    }

    /**
     * Получает id инфоблока, бросает исключение если его не существует
     * @param $code string|array - код или фильтр
     * @param string $typeId
     * @throws HelperException
     * @return mixed
     */
    public function getIblockIdIfExists($code, $typeId = '')
    {
        $item = $this->getIblock($code, $typeId);
        if ($item && isset($item['ID'])) {
            return $item['ID'];
        }

        $this->throwException(__METHOD__, "iblock id not found");
    }

    /**
     * Получает тип инфоблока
     * @param $typeId
     * @return array
     */
    public function getIblockType($typeId)
    {
        /** @compatibility filter or $typeId */
        $filter = is_array($typeId) ? $typeId : [
            '=ID' => $typeId,
        ];

        $filter['CHECK_PERMISSIONS'] = 'N';
        $item = CIBlockType::GetList(['SORT' => 'ASC'], $filter)->Fetch();

        if ($item) {
            $item['LANG'] = $this->getIblockTypeLangs($item['ID']);
        }

        return $item;
    }

    /**
     * Получает id типа инфоблока
     * @param $typeId
     * @return int|mixed
     */
    public function getIblockTypeId($typeId)
    {
        $iblockType = $this->getIblockType($typeId);
        return ($iblockType && isset($iblockType['ID'])) ? $iblockType['ID'] : 0;
    }

    /**
     * Получает типы инфоблоков
     * @param array $filter
     * @return array
     */
    public function getIblockTypes($filter = [])
    {
        $filter['CHECK_PERMISSIONS'] = 'N';
        $dbres = CIBlockType::GetList(['SORT' => 'ASC'], $filter);

        $list = [];
        while ($item = $dbres->Fetch()) {
            $item['LANG'] = $this->getIblockTypeLangs($item['ID']);
            $list[] = $item;
        }
        return $list;
    }

    /**
     * Добавляет тип инфоблока, если его не существует
     * @param array $fields , обязательные параметры - id типа инфоблока
     * @throws HelperException
     * @return mixed
     */
    public function addIblockTypeIfNotExists($fields = [])
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['ID']);

        $iblockType = $this->getIblockType($fields['ID']);
        if ($iblockType) {
            return $iblockType['ID'];
        }

        return $this->addIblockType($fields);
    }

    /**
     * Добавляет тип инфоблока
     * @param array $fields
     * @throws HelperException
     * @return mixed
     */
    public function addIblockType($fields = [])
    {
        $default = [
            'ID' => '',
            'SECTIONS' => 'Y',
            'IN_RSS' => 'N',
            'SORT' => 100,
            'LANG' => [
                'ru' => [
                    'NAME' => 'Catalog',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements',
                ],
                'en' => [
                    'NAME' => 'Catalog',
                    'SECTION_NAME' => 'Sections',
                    'ELEMENT_NAME' => 'Elements',
                ],
            ],
        ];

        $fields = array_replace_recursive($default, $fields);

        $ib = new CIBlockType;
        if ($ib->Add($fields)) {
            return $fields['ID'];
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Обновляет тип инфоблока
     * @param $iblockTypeId
     * @param array $fields
     * @throws HelperException
     * @return mixed
     */
    public function updateIblockType($iblockTypeId, $fields = [])
    {
        $ib = new CIBlockType;
        if ($ib->Update($iblockTypeId, $fields)) {
            return $iblockTypeId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Удаляет тип инфоблока, если существует
     * @param $typeId
     * @throws HelperException
     * @return bool
     */
    public function deleteIblockTypeIfExists($typeId)
    {
        $iblockType = $this->getIblockType($typeId);
        if (!$iblockType) {
            return false;
        }

        return $this->deleteIblockType($iblockType['ID']);

    }

    /**
     * Удаляет тип инфоблока
     * @param $typeId
     * @throws HelperException
     * @return bool
     */
    public function deleteIblockType($typeId)
    {
        if (CIBlockType::Delete($typeId)) {
            return true;
        }

        $this->throwException(__METHOD__, 'Could not delete iblock type %s', $typeId);
    }

    /**
     * Получает инфоблок
     * @param $code string|array - код или фильтр
     * @param string $typeId
     * @return mixed
     */
    public function getIblock($code, $typeId = '')
    {
        /** @compatibility filter or code */
        $filter = is_array($code) ? $code : [
            '=CODE' => $code,
        ];

        if (!empty($typeId)) {
            $filter['=TYPE'] = $typeId;
        }

        $filter['CHECK_PERMISSIONS'] = 'N';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */

        $item = CIBlock::GetList(['SORT' => 'ASC'], $filter)->Fetch();
        return $this->prepareIblock($item);
    }

    /**
     * Получает список сайтов для инфоблока
     * @param $iblockId
     * @return array
     */
    public function getIblockSites($iblockId)
    {
        $dbres = CIBlock::GetSite($iblockId);
        return $this->fetchAll($dbres, false, 'LID');
    }

    /**
     * Получает id инфоблока
     * @param $code string|array - код или фильтр
     * @param string $typeId
     * @return int
     */
    public function getIblockId($code, $typeId = '')
    {
        $iblock = $this->getIblock($code, $typeId);
        return ($iblock && isset($iblock['ID'])) ? $iblock['ID'] : 0;
    }

    /**
     * Получает список инфоблоков
     * @param array $filter
     * @return array
     */
    public function getIblocks($filter = [])
    {
        $filter['CHECK_PERMISSIONS'] = 'N';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = CIBlock::GetList(['SORT' => 'ASC'], $filter);
        $list = [];
        while ($item = $dbres->Fetch()) {
            $list[] = $this->prepareIblock($item);
        }
        return $list;
    }

    /**
     * Добавляет инфоблок если его не существует
     * @param array $fields , обязательные параметры - код, тип инфоблока, id сайта
     * @throws HelperException
     * @return bool
     */
    public function addIblockIfNotExists($fields = [])
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE', 'IBLOCK_TYPE_ID', 'LID']);

        $typeId = false;
        if (!empty($fields['IBLOCK_TYPE_ID'])) {
            $typeId = $fields['IBLOCK_TYPE_ID'];
        }

        $iblock = $this->getIblock($fields['CODE'], $typeId);
        if ($iblock) {
            return $iblock['ID'];
        }

        return $this->addIblock($fields);
    }

    /**
     * Добавляет инфоблок
     * @param $fields , обязательные параметры - код, тип инфоблока, id сайта
     * @throws HelperException
     * @return bool
     */
    public function addIblock($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE', 'IBLOCK_TYPE_ID', 'LID']);

        $default = [
            'ACTIVE' => 'Y',
            'NAME' => '',
            'CODE' => '',
            'LIST_PAGE_URL' => '',
            'DETAIL_PAGE_URL' => '',
            'SECTION_PAGE_URL' => '',
            'IBLOCK_TYPE_ID' => 'main',
            'LID' => ['s1'],
            'SORT' => 500,
            'GROUP_ID' => ['2' => 'R'],
            'VERSION' => 2,
            'BIZPROC' => 'N',
            'WORKFLOW' => 'N',
            'INDEX_ELEMENT' => 'N',
            'INDEX_SECTION' => 'N',
        ];

        $fields = array_replace_recursive($default, $fields);

        $ib = new CIBlock;
        $iblockId = $ib->Add($fields);

        if ($iblockId) {
            return $iblockId;
        }
        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Обновляет инфоблок
     * @param $iblockId
     * @param array $fields
     * @throws HelperException
     * @return mixed
     */
    public function updateIblock($iblockId, $fields = [])
    {
        $ib = new CIBlock;
        if ($ib->Update($iblockId, $fields)) {
            return $iblockId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);

    }

    /**
     * Обновляет инфоблок если он существует
     * @param $code
     * @param array $fields
     * @throws HelperException
     * @return bool|mixed
     */
    public function updateIblockIfExists($code, $fields = [])
    {
        $iblock = $this->getIblock($code);
        if (!$iblock) {
            return false;
        }
        return $this->updateIblock($iblock['ID'], $fields);
    }

    /**
     * Удаляет инфоблок если он существует
     * @param $code
     * @param string $typeId
     * @throws HelperException
     * @return bool
     */
    public function deleteIblockIfExists($code, $typeId = '')
    {
        $iblock = $this->getIblock($code, $typeId);
        if (!$iblock) {
            return false;
        }
        return $this->deleteIblock($iblock['ID']);
    }

    /**
     * Удаляет инфоблок
     * @param $iblockId
     * @throws HelperException
     * @return bool
     */
    public function deleteIblock($iblockId)
    {
        if (CIBlock::Delete($iblockId)) {
            return true;
        }
        $this->throwException(__METHOD__, 'Could not delete iblock %s', $iblockId);
    }

    /**
     * Получает список полей инфоблока
     * @param $iblockId
     * @return array|bool
     */
    public function getIblockFields($iblockId)
    {
        return CIBlock::GetFields($iblockId);
    }

    /**
     * Получает свойство инфоблока
     * @param $iblockId
     * @param $code int|array - код или фильтр
     * @return mixed
     */
    public function getProperty($iblockId, $code)
    {
        /** @compatibility filter or code */
        $filter = is_array($code) ? $code : [
            'CODE' => $code,
        ];

        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';
        /* do not use =CODE in filter */
        $property = CIBlockProperty::GetList(['SORT' => 'ASC'], $filter)->Fetch();
        return $this->prepareProperty($property);
    }

    /**
     * Получает значения списков для свойств инфоблоков
     * @param array $filter
     * @return array
     */
    public function getPropertyEnums($filter = [])
    {
        $result = [];

        $dbres = CIBlockPropertyEnum::GetList(["SORT" => "ASC", "VALUE" => "ASC"], $filter);
        while ($item = $dbres->Fetch()) {
            $result[] = $item;
        }
        return $result;
    }

    /**
     * Получает значения списков для свойства инфоблока
     * @param $iblockId
     * @param $propertyId
     * @return array
     */
    public function getPropertyEnumValues($iblockId, $propertyId)
    {
        return $this->getPropertyEnums([
            'IBLOCK_ID' => $iblockId,
            'PROPERTY_ID' => $propertyId,
        ]);
    }

    /**
     * Получает свойство инфоблока
     * @param $iblockId
     * @param $code int|array - код или фильтр
     * @return int
     */
    public function getPropertyId($iblockId, $code)
    {
        $item = $this->getProperty($iblockId, $code);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    /**
     * Получает свойства инфоблока
     * @param $iblockId
     * @param array $filter
     * @return array
     */
    public function getProperties($iblockId, $filter = [])
    {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        $filterIds = false;
        if (isset($filter['ID']) && is_array($filter['ID'])) {
            $filterIds = $filter['ID'];
            unset($filter['ID']);
        }

        $dbres = CIBlockProperty::GetList(['SORT' => 'ASC'], $filter);

        $result = [];

        while ($property = $dbres->Fetch()) {
            if ($filterIds) {
                if (in_array($property['ID'], $filterIds)) {
                    $result[] = $this->prepareProperty($property);
                }
            } else {
                $result[] = $this->prepareProperty($property);
            }
        }
        return $result;
    }

    /**
     * Добавляет свойство инфоблока если его не существует
     * @param $iblockId
     * @param $fields , обязательные параметры - код свойства
     * @throws HelperException
     * @return bool
     */
    public function addPropertyIfNotExists($iblockId, $fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE']);

        $property = $this->getProperty($iblockId, $fields['CODE']);
        if ($property) {
            return $property['ID'];
        }

        return $this->addProperty($iblockId, $fields);

    }

    /**
     * Добавляет свойство инфоблока
     * @param $iblockId
     * @param $fields
     * @throws HelperException
     * @return bool
     */
    public function addProperty($iblockId, $fields)
    {

        $default = [
            'NAME' => '',
            'ACTIVE' => 'Y',
            'SORT' => '500',
            'CODE' => '',
            'PROPERTY_TYPE' => 'S',
            'USER_TYPE' => '',
            'ROW_COUNT' => '1',
            'COL_COUNT' => '30',
            'LIST_TYPE' => 'L',
            'MULTIPLE' => 'N',
            'IS_REQUIRED' => 'N',
            'FILTRABLE' => 'Y',
            'LINK_IBLOCK_ID' => 0,
        ];

        if (!empty($fields['VALUES'])) {
            $default['PROPERTY_TYPE'] = 'L';
        }

        if (!empty($fields['LINK_IBLOCK_ID'])) {
            $default['PROPERTY_TYPE'] = 'E';
        }

        $fields = array_replace_recursive($default, $fields);

        if (false !== strpos($fields['PROPERTY_TYPE'], ':')) {
            list($ptype, $utype) = explode(':', $fields['PROPERTY_TYPE']);
            $fields['PROPERTY_TYPE'] = $ptype;
            $fields['USER_TYPE'] = $utype;
        }

        if (false !== strpos($fields['LINK_IBLOCK_ID'], ':')) {
            list($ibtype, $ibcode) = explode(':', $fields['LINK_IBLOCK_ID']);
            $fields['LINK_IBLOCK_ID'] = $this->getIblockId($ibcode, $ibtype);
        }

        $fields['IBLOCK_ID'] = $iblockId;

        $ib = new CIBlockProperty;
        $propertyId = $ib->Add($fields);

        if ($propertyId) {
            return $propertyId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Удаляет свойство инфоблока если оно существует
     * @param $iblockId
     * @param $code
     * @throws HelperException
     * @return bool
     */
    public function deletePropertyIfExists($iblockId, $code)
    {
        $property = $this->getProperty($iblockId, $code);
        if (!$property) {
            return false;
        }

        return $this->deletePropertyById($property['ID']);

    }

    /**
     * Удаляет свойство инфоблока
     * @param $propertyId
     * @throws HelperException
     * @return bool
     */
    public function deletePropertyById($propertyId)
    {
        $ib = new CIBlockProperty;
        if ($ib->Delete($propertyId)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Обновляет свойство инфоблока если оно существует
     * @param $iblockId
     * @param $code
     * @param $fields
     * @throws HelperException
     * @return bool|mixed
     */
    public function updatePropertyIfExists($iblockId, $code, $fields)
    {
        $property = $this->getProperty($iblockId, $code);
        if (!$property) {
            return false;
        }
        return $this->updatePropertyById($property['ID'], $fields);
    }

    /**
     * Обновляет свойство инфоблока
     * @param $propertyId
     * @param $fields
     * @throws HelperException
     * @return mixed
     */
    public function updatePropertyById($propertyId, $fields)
    {
        if (!empty($fields['VALUES']) && !isset($fields['PROPERTY_TYPE'])) {
            $fields['PROPERTY_TYPE'] = 'L';
        }

        if (!empty($fields['LINK_IBLOCK_ID']) && !isset($fields['PROPERTY_TYPE'])) {
            $fields['PROPERTY_TYPE'] = 'E';
        }

        if (false !== strpos($fields['PROPERTY_TYPE'], ':')) {
            list($ptype, $utype) = explode(':', $fields['PROPERTY_TYPE']);
            $fields['PROPERTY_TYPE'] = $ptype;
            $fields['USER_TYPE'] = $utype;
        }

        if (false !== strpos($fields['LINK_IBLOCK_ID'], ':')) {
            list($ibtype, $ibcode) = explode(':', $fields['LINK_IBLOCK_ID']);
            $fields['LINK_IBLOCK_ID'] = $this->getIblockId($ibcode, $ibtype);
        }

        if (isset($fields['VALUES']) && is_array($fields['VALUES'])) {
            $existsEnums = $this->getPropertyEnums([
                'PROPERTY_ID' => $propertyId,
            ]);

            $newValues = [];
            foreach ($fields['VALUES'] as $index => $item) {
                foreach ($existsEnums as $existsEnum) {
                    if ($existsEnum['XML_ID'] == $item['XML_ID']) {
                        $item['ID'] = $existsEnum['ID'];
                        break;
                    }
                }

                if (!empty($item['ID'])) {
                    $newValues[$item['ID']] = $item;
                } else {
                    $newValues['n' . $index] = $item;
                }

            }

            $fields['VALUES'] = $newValues;
        }


        $ib = new CIBlockProperty();
        if ($ib->Update($propertyId, $fields)) {
            return $propertyId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Получает элемент инфоблока
     * @param $iblockId
     * @param $code
     * @param array $select
     * @return array
     */
    public function getElement($iblockId, $code, $select = [])
    {
        /** @compatibility filter or code */
        $filter = is_array($code) ? $code : [
            '=CODE' => $code,
        ];

        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        $select = array_merge([
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
        ], $select);

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return CIBlockElement::GetList([
            'SORT' => 'ASC',
        ], $filter, false, [
            'nTopCount' => 1,
        ], $select)->Fetch();
    }

    /**
     * Получает id элемента инфоблока
     * @param $iblockId
     * @param $code
     * @return int|mixed
     */
    public function getElementId($iblockId, $code)
    {
        $item = $this->getElement($iblockId, $code);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    /**
     * Получает элементы инфоблока
     * @param $iblockId
     * @param array $filter
     * @param array $select
     * @return array
     */
    public function getElements($iblockId, $filter = [], $select = [])
    {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        $select = array_merge([
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
        ], $select);

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = CIBlockElement::GetList([
            'ID' => 'ASC',
        ], $filter, false, false, $select);

        $list = [];
        while ($item = $dbres->Fetch()) {
            $list[] = $item;
        }
        return $list;
    }

    /**
     * Добавляет элемент инфоблока если он не существует
     * @param $iblockId
     * @param $fields , обязательные параметры - код элемента
     * @param array $props
     * @throws HelperException
     * @return bool|mixed
     */
    public function addElementIfNotExists($iblockId, $fields, $props = [])
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE']);

        $item = $this->getElement($iblockId, $fields['CODE']);
        if ($item) {
            return $item['ID'];
        }

        return $this->addElement($iblockId, $fields, $props);
    }

    /**
     * Добавляет элемент инфоблока
     * @param $iblockId
     * @param array $fields - поля
     * @param array $props - свойства
     * @throws HelperException
     * @return bool
     */
    public function addElement($iblockId, $fields = [], $props = [])
    {
        $default = [
            "NAME" => "element",
            "IBLOCK_SECTION_ID" => false,
            "ACTIVE" => "Y",
            "PREVIEW_TEXT" => "",
            "DETAIL_TEXT" => "",
        ];

        $fields = array_replace_recursive($default, $fields);
        $fields["IBLOCK_ID"] = $iblockId;

        if (!empty($props)) {
            $fields['PROPERTY_VALUES'] = $props;
        }

        $ib = new CIBlockElement;
        $id = $ib->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Обновляет элемент инфоблока если он существует
     * @param $iblockId
     * @param array $fields , обязательные параметры - код элемента
     * @param array $props
     * @throws HelperException
     * @return bool|mixed
     */
    public function updateElementIfExists($iblockId, $fields = [], $props = [])
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE']);

        $item = $this->getElement($iblockId, $fields['CODE']);
        if (!$item) {
            return false;
        }

        $fields['IBLOCK_ID'] = $iblockId;
        unset($fields['CODE']);

        return $this->updateElement($item['ID'], $fields, $props);
    }

    /**
     * Обновляет элемент инфоблока
     * @param $elementId
     * @param array $fields
     * @param array $props
     * @throws HelperException
     * @return mixed
     */
    public function updateElement($elementId, $fields = [], $props = [])
    {
        $iblockId = !empty($fields['IBLOCK_ID']) ? $fields['IBLOCK_ID'] : false;
        unset($fields['IBLOCK_ID']);

        if (!empty($fields)) {
            $ib = new CIBlockElement;
            if (!$ib->Update($elementId, $fields)) {
                $this->throwException(__METHOD__, $ib->LAST_ERROR);
            }
        }

        if (!empty($props)) {
            CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, $props);
        }

        return $elementId;
    }

    /**
     * Удаляет элемент инфоблока если он существует
     * @param $iblockId
     * @param $code
     * @throws HelperException
     * @return bool
     */
    public function deleteElementIfExists($iblockId, $code)
    {
        $item = $this->getElement($iblockId, $code);

        if (!$item) {
            return false;
        }

        return $this->deleteElement($item['ID']);
    }

    /**
     * Удаляет элемент инфоблока
     * @param $elementId
     * @throws HelperException
     * @return bool
     */
    public function deleteElement($elementId)
    {
        $ib = new CIBlockElement;
        if ($ib->Delete($elementId)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Получает секцию инфоблока
     * @param $iblockId
     * @param $code string|array - код или фильтр
     * @return array
     */
    public function getSection($iblockId, $code)
    {
        /** @compatibility filter or code */
        $filter = is_array($code) ? $code : [
            '=CODE' => $code,
        ];

        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return CIBlockSection::GetList([
            'ID' => 'ASC',
        ], $filter, false, [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
        ])->Fetch();
    }

    /**
     * Получает id секции инфоблока
     * @param $iblockId
     * @param $code string|array - код или фильтр
     * @return int|mixed
     */
    public function getSectionId($iblockId, $code)
    {
        $item = $this->getSection($iblockId, $code);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    /**
     * Получает секции инфоблока
     * @param $iblockId
     * @param array $filter
     * @return array
     */
    public function getSections($iblockId, $filter = [])
    {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = CIBlockSection::GetList([
            'SORT' => 'ASC',
        ], $filter, false, [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
        ]);

        $list = [];
        while ($item = $dbres->Fetch()) {
            $list[] = $item;
        }
        return $list;
    }

    /**
     * Добавляет секцию инфоблока если она не существует
     * @param $iblockId
     * @param $fields , обязательные параметры - код сеции
     * @throws HelperException
     * @return bool|int|mixed
     */
    public function addSectionIfNotExists($iblockId, $fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE']);

        $item = $this->getSection($iblockId, $fields['CODE']);
        if ($item) {
            return $item['ID'];
        }

        return $this->addSection($iblockId, $fields);

    }

    /**
     * Добавляет секцию инфоблока
     * @param $iblockId
     * @param array $fields
     * @throws HelperException
     * @return bool|int
     */
    public function addSection($iblockId, $fields = [])
    {
        $default = [
            "ACTIVE" => "Y",
            "IBLOCK_SECTION_ID" => false,
            "NAME" => 'section',
            "CODE" => '',
            "SORT" => 100,
            "PICTURE" => false,
            "DESCRIPTION" => '',
            "DESCRIPTION_TYPE" => 'text',
        ];

        $fields = array_replace_recursive($default, $fields);
        $fields["IBLOCK_ID"] = $iblockId;

        $ib = new CIBlockSection;
        $id = $ib->Add($fields);

        if ($id) {
            return $id;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Обновляет секцию инфоблока если она существует
     * @param $iblockId
     * @param $fields , обязательные параметры - код секции
     * @throws HelperException
     * @return bool|mixed
     */
    public function updateSectionIfExists($iblockId, $fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE']);

        $item = $this->getSection($iblockId, $fields['CODE']);
        if (!$item) {
            return false;
        }

        unset($fields['CODE']);

        return $this->updateSection($item['ID'], $fields);

    }

    /**
     * Обновляет секцию инфоблока
     * @param $sectionId
     * @param $fields
     * @throws HelperException
     * @return mixed
     */
    public function updateSection($sectionId, $fields)
    {
        $ib = new CIBlockSection;
        if ($ib->Update($sectionId, $fields)) {
            return $sectionId;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Удаляет секцию инфоблока если она существует
     * @param $iblockId
     * @param $code
     * @throws HelperException
     * @return bool
     */
    public function deleteSectionIfExists($iblockId, $code)
    {
        $item = $this->getSection($iblockId, $code);
        if (!$item) {
            return false;
        }

        return $this->deleteSection($item['ID']);

    }

    /**
     * Удаляет секцию инфоблока
     * @param $sectionId
     * @throws HelperException
     * @return bool
     */
    public function deleteSection($sectionId)
    {
        $ib = new CIBlockSection;
        if ($ib->Delete($sectionId)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

    /**
     * Получает языковые названия для типа инфоблока
     * @param $typeId
     * @return array
     */
    public function getIblockTypeLangs($typeId)
    {
        $result = [];
        $dbres = CLanguage::GetList($lby = "sort", $lorder = "asc");
        while ($item = $dbres->GetNext()) {
            $values = CIBlockType::GetByIDLang($typeId, $item['LID'], false);
            if (!empty($values)) {
                $result[$item['LID']] = [
                    'NAME' => $values['NAME'],
                    'SECTION_NAME' => $values['SECTION_NAME'],
                    'ELEMENT_NAME' => $values['ELEMENT_NAME'],
                ];
            }
        }
        return $result;
    }

    /**
     * Сохраняет тип инфоблока
     * Создаст если не было, обновит если существует и отличается
     * @param array $fields , обязательные параметры - тип инфоблока
     * @throws HelperException
     * @return bool|mixed
     */
    public function saveIblockType($fields = [])
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['ID']);

        $item = $this->getIblockType($fields['ID']);
        $exists = $this->prepareExportIblockType($item);
        $fields = $this->prepareExportIblockType($fields);

        if (empty($item)) {
            $ok = $this->getMode('test') ? true : $this->addIblockType($fields);
            $this->outNoticeIf($ok, 'Тип инфоблока %s: добавлен', $fields['ID']);
            return $ok;
        }


        if ($this->hasDiff($exists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updateIblockType($item['ID'], $fields);
            $this->outNoticeIf($ok, 'Тип инфоблока %s: обновлен', $fields['ID']);
            $this->outDiffIf($ok, $exists, $fields);

            return $ok;
        }

        $ok = $this->getMode('test') ? true : $fields['ID'];
        if ($this->getMode('out_equal')) {
            $this->outIf($ok, 'Тип инфоблока %s: совпадает', $fields['ID']);
        }
        return $ok;
    }

    /**
     * Сохраняет инфоблок
     * Создаст если не было, обновит если существует и отличается
     * @param array $fields , обязательные параметры - код, тип инфоблока, id сайта
     * @throws HelperException
     * @return bool|mixed
     */
    public function saveIblock($fields = [])
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE', 'IBLOCK_TYPE_ID', 'LID']);

        $item = $this->getIblock($fields['CODE'], $fields['IBLOCK_TYPE_ID']);
        $exists = $this->prepareExportIblock($item);
        $fields = $this->prepareExportIblock($fields);

        if (empty($item)) {
            $ok = $this->getMode('test') ? true : $this->addIblock($fields);
            $this->outNoticeIf($ok, 'Инфоблок %s: добавлен', $fields['CODE']);
            return $ok;
        }

        if ($this->hasDiff($exists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updateIblock($item['ID'], $fields);
            $this->outNoticeIf($ok, 'Инфоблок %s: обновлен', $fields['CODE']);
            $this->outDiffIf($ok, $exists, $fields);
            return $ok;
        }

        $ok = $this->getMode('test') ? true : $item['ID'];
        if ($this->getMode('out_equal')) {
            $this->outIf($ok, 'Инфоблок %s: совпадает', $fields['CODE']);
        }
        return $ok;
    }

    /**
     * Сохраняет поля инфоблока
     * @param $iblockId
     * @param array $fields
     * @return bool
     */
    public function saveIblockFields($iblockId, $fields = [])
    {
        $exists = CIBlock::GetFields($iblockId);

        $exportExists = $this->prepareExportIblockFields($exists);
        $fields = $this->prepareExportIblockFields($fields);

        $fields = array_replace_recursive($exportExists, $fields);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->updateIblockFields($iblockId, $fields);
            $this->outNoticeIf($ok, 'Инфоблок %s: поля добавлены', $iblockId);
            return $ok;
        }

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updateIblockFields($iblockId, $fields);
            $this->outNoticeIf($ok, 'Инфоблок %s: поля обновлены', $iblockId);
            $this->outDiffIf($ok, $exportExists, $fields);
            return $ok;
        }

        if ($this->getMode('out_equal')) {
            $this->out('Инфоблок %s: поля совпадают', $iblockId);
        }

        return true;
    }

    /**
     * Сохраняет свойство инфоблока
     * Создаст если не было, обновит если существует и отличается
     * @param $iblockId
     * @param $fields , обязательные параметры - код свойства
     * @throws HelperException
     * @return bool|mixed
     */
    public function saveProperty($iblockId, $fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['CODE']);

        $exists = $this->getProperty($iblockId, $fields['CODE']);
        $exportExists = $this->prepareExportProperty($exists);
        $fields = $this->prepareExportProperty($fields);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->addProperty($iblockId, $fields);
            $this->outNoticeIf($ok, 'Инфоблок %s: свойство %s добавлено', $iblockId, $fields['CODE']);
            return $ok;
        }

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updatePropertyById($exists['ID'], $fields);
            $this->outNoticeIf($ok, 'Инфоблок %s: свойство %s обновлено', $iblockId, $fields['CODE']);
            $this->outDiffIf($ok, $exportExists, $fields);
            return $ok;
        }

        $ok = $this->getMode('test') ? true : $exists['ID'];
        if ($this->getMode('out_equal')) {
            $this->outIf($ok, 'Инфоблок %s: свойство %s совпадает', $iblockId, $fields['CODE']);
        }
        return $ok;
    }

    /**
     * Получает тип инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $typeId
     * @return mixed
     */
    public function exportIblockType($typeId)
    {
        return $this->prepareExportIblockType(
            $this->getIblockType($typeId)
        );
    }

    /**
     * Получает инфоблок
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $iblockId
     * @throws HelperException
     * @return mixed
     */
    public function exportIblock($iblockId)
    {
        $export = $this->prepareExportIblock(
            $this->getIblock(['ID' => $iblockId])
        );

        if (!empty($export['CODE'])) {
            return $export;
        }

        $this->throwException(__METHOD__, 'code not found');
    }

    /**
     * Получает список инфоблоков
     * Данные подготовлены для экспорта в миграцию или схему
     * @param array $filter
     * @return array
     */
    public function exportIblocks($filter = [])
    {
        $exports = [];
        $items = $this->getIblocks($filter);
        foreach ($items as $item) {
            if (!empty($item['CODE'])) {
                $exports[] = $this->prepareExportIblock($item);
            }
        }
        return $exports;
    }

    /**
     * Получает список полей инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $iblockId
     * @return array
     */
    public function exportIblockFields($iblockId)
    {
        return $this->prepareExportIblockFields(
            $this->getIblockFields($iblockId)
        );
    }

    /**
     * Получает свойство инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $iblockId
     * @param bool $code
     * @throws HelperException
     * @return mixed
     */
    public function exportProperty($iblockId, $code = false)
    {
        $export = $this->prepareExportProperty(
            $this->getProperty($iblockId, $code)
        );

        if (!empty($export['CODE'])) {
            return $export;
        }

        $this->throwException(__METHOD__, 'code not found');
    }

    /**
     * Получает свойства инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $iblockId
     * @param array $filter
     * @return array
     */
    public function exportProperties($iblockId, $filter = [])
    {
        $exports = [];
        $items = $this->getProperties($iblockId, $filter);
        foreach ($items as $item) {
            if (!empty($item['CODE'])) {
                $exports[] = $this->prepareExportProperty($item);
            }
        }
        return $exports;
    }

    /**
     * Обновляет поля инфоблока
     * @param $iblockId
     * @param $fields
     * @return bool
     */
    public function updateIblockFields($iblockId, $fields)
    {
        if ($iblockId && !empty($fields)) {
            CIBlock::SetFields($iblockId, $fields);
            return true;
        }
        return false;
    }


    /**
     * Получает права доступа к инфоблоку для групп
     * возвращает массив вида [$groupId => $letter]
     *
     * @param $iblockId
     * @return array
     */
    public function getGroupPermissions($iblockId)
    {
        return CIBlock::GetGroupPermissions($iblockId);
    }

    /**
     * Устанавливает права доступа к инфоблоку для групп
     * предыдущие права сбрасываются
     * принимает массив вида [$groupId => $letter]
     *
     * @param $iblockId
     * @param array $permissions
     */
    public function setGroupPermissions($iblockId, $permissions = [])
    {
        CIBlock::SetPermission($iblockId, $permissions);
    }

    /**
     * @param $iblockId
     * @param $code
     * @throws HelperException
     * @return bool
     * @deprecated
     */
    public function deleteProperty($iblockId, $code)
    {
        return $this->deletePropertyIfExists($iblockId, $code);
    }

    /**
     * @param $iblockId
     * @param $code
     * @param $fields
     * @throws HelperException
     * @return bool|mixed
     * @deprecated
     */
    public function updateProperty($iblockId, $code, $fields)
    {
        return $this->updatePropertyIfExists($iblockId, $code, $fields);
    }

    /**
     * @param $iblockId
     * @param $fields
     * @deprecated
     */
    public function mergeIblockFields($iblockId, $fields)
    {
        $this->saveIblockFields($iblockId, $fields);
    }

    /**
     * @param $typeId
     * @throws HelperException
     * @return array
     * @deprecated
     */
    public function findIblockType($typeId)
    {
        return $this->getIblockTypeIfExists($typeId);
    }

    /**
     * @param $code
     * @param string $typeId
     * @throws HelperException
     * @return mixed
     * @deprecated
     */
    public function findIblockId($code, $typeId = '')
    {
        return $this->getIblockIdIfExists($code, $typeId);
    }

    /**
     * @param $code
     * @param string $typeId
     * @throws HelperException
     * @return mixed
     * @deprecated
     */
    public function findIblock($code, $typeId = '')
    {
        return $this->getIblockIfExists($code, $typeId);
    }

    /**
     * @param $typeId
     * @throws HelperException
     * @return mixed
     * @deprecated
     */
    public function findIblockTypeId($typeId)
    {
        return $this->getIblockTypeIdIfExists($typeId);
    }

    protected function prepareIblock($item)
    {
        if (empty($item['ID'])) {
            return $item;
        }
        $item['LID'] = $this->getIblockSites($item['ID']);
        return $item;
    }

    protected function prepareProperty($property)
    {
        if ($property && $property['PROPERTY_TYPE'] == 'L' && $property['IBLOCK_ID'] && $property['ID']) {
            $property['VALUES'] = $this->getPropertyEnums([
                'IBLOCK_ID' => $property['IBLOCK_ID'],
                'PROPERTY_ID' => $property['ID'],
            ]);
        }
        return $property;
    }

    protected function prepareExportIblockType($item)
    {
        if (empty($item)) {
            return $item;
        }

        return $item;
    }

    protected function prepareExportIblockFields($fields)
    {
        if (empty($fields)) {
            return $fields;
        }

        $exportFields = [];
        foreach ($fields as $code => $field) {
            if ($field["VISIBLE"] == "N" || preg_match("/^(LOG_)/", $code)) {
                continue;
            }
            $exportFields[$code] = $field;
        }

        return $exportFields;
    }

    protected function prepareExportIblock($iblock)
    {
        if (empty($iblock)) {
            return $iblock;
        }

        unset($iblock['ID']);
        unset($iblock['TIMESTAMP_X']);
        unset($iblock['TMP_ID']);

        return $iblock;
    }

    protected function prepareExportProperty($prop)
    {
        if (empty($prop)) {
            return $prop;
        }

        unset($prop['ID']);
        unset($prop['IBLOCK_ID']);
        unset($prop['TIMESTAMP_X']);
        unset($prop['TMP_ID']);

        if (!empty($prop['VALUES']) && is_array($prop['VALUES'])) {
            $exportValues = [];

            foreach ($prop['VALUES'] as $item) {
                unset($item['ID']);
                unset($item['TMP_ID']);
                unset($item['PROPERTY_ID']);
                unset($item['EXTERNAL_ID']);
                unset($item['PROPERTY_NAME']);
                unset($item['PROPERTY_CODE']);
                unset($item['PROPERTY_SORT']);
                $exportValues[] = $item;
            }

            $prop['VALUES'] = $exportValues;
        }

        if (!empty($prop['LINK_IBLOCK_ID'])) {
            $linked = $this->getIblock([
                'ID' => $prop['LINK_IBLOCK_ID'],
            ]);

            if (!empty($linked['CODE'])) {
                $prop['LINK_IBLOCK_ID'] = $linked['IBLOCK_TYPE_ID'] . ':' . $linked['CODE'];
            }
        }

        return $prop;
    }
}