<?php

namespace Sprint\Migration\Helpers\Traits\Iblock;

use CIBlockElement;
use Sprint\Migration\Exceptions\HelperException;

trait IblockElementTrait
{

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
     * @param $iblockId
     * @param array $filter
     * @return int
     */
    public function getElementsCount($iblockId, $filter = [])
    {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        return (int)CIBlockElement::GetList(
            [],
            $filter,
            [],
            false,
            ['ID', 'NAME']
        );
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
     * @return int|void
     */
    public function addElement($iblockId, $fields = [], $props = [])
    {
        $default = [
            'NAME' => 'element',
            'IBLOCK_SECTION_ID' => false,
            'ACTIVE' => 'Y',
            'PREVIEW_TEXT' => '',
            'DETAIL_TEXT' => '',
        ];

        $fields = array_replace_recursive($default, $fields);
        $fields['IBLOCK_ID'] = $iblockId;

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
     * @return bool|int|void
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
     * @return int
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
     * @return bool|void
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
     * @return bool|void
     */
    public function deleteElement($elementId)
    {
        $ib = new CIBlockElement;
        if ($ib->Delete($elementId)) {
            return true;
        }

        $this->throwException(__METHOD__, $ib->LAST_ERROR);
    }

}
