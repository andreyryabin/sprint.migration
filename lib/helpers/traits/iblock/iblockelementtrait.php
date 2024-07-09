<?php

namespace Sprint\Migration\Helpers\Traits\Iblock;

use CIBlockElement;
use CIBlockResult;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;

trait IblockElementTrait
{
    /**
     * Получает id элемента инфоблока
     *
     * @param $iblockId
     * @param $code
     *
     * @return int|mixed
     */
    public function getElementId($iblockId, $code)
    {
        $item = $this->getElement($iblockId, $code);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    /**
     * Получает элемент инфоблока
     *
     * @param int          $iblockId
     * @param array|string $code
     * @param array        $select
     *
     * @return array
     */
    public function getElement($iblockId, $code, $select = [])
    {
        /** @compatibility filter or code */
        $filter = is_array($code)
            ? $code
            : [
                '=CODE' => $code,
            ];

        $select = array_merge(
            [
                'ID',
                'XML_ID',
                'IBLOCK_ID',
                'NAME',
                'CODE',
                'ACTIVE',
            ], $select
        );

        $item = $this->getElementsList($iblockId, [
            'filter' => $filter,
            'select' => $select,
            'limit'  => 1,
        ])->Fetch();

        return $this->prepareElement($item);
    }

    /**
     * @param $item
     *
     * @return mixed
     */
    protected function prepareElement($item)
    {
        if (empty($item['ID'])) {
            return $item;
        }

        $item['IBLOCK_SECTION'] = $this->getElementSectionIds($item['ID']);

        return $item;
    }

    /**
     * @param $elementId
     *
     * @return array
     */
    public function getElementSectionIds($elementId)
    {
        $sections = $this->getElementSections($elementId);
        return array_column($sections, 'ID');
    }

    /**
     * @param       $elementId
     * @param array $sectionSelect
     *
     * @return mixed
     */
    public function getElementSections($elementId, $sectionSelect = [])
    {
        $dbres = CIBlockElement::GetElementGroups($elementId, true, $sectionSelect);
        return $this->fetchAll($dbres);
    }

    /**
     * Получает элементы инфоблока
     *
     * @param int   $iblockId
     * @param array $filter
     * @param array $select
     *
     * @return array
     */
    public function getElements($iblockId, $filter = [], $select = []): array
    {
        $select = array_merge(
            [
                'ID',
                'XML_ID',
                'IBLOCK_ID',
                'NAME',
                'CODE',
                'ACTIVE',
            ], $select
        );

        $dbres = $this->getElementsList(
            $iblockId,
            [
                'filter' => $filter,
                'select' => $select,
            ]
        );

        $list = [];
        while ($item = $dbres->Fetch()) {
            $list[] = $this->prepareElement($item);
        }
        return $list;
    }

    public function getElementsList($iblockId, $params = []): CIBlockResult
    {
        $params = array_merge(
            [
                'offset' => 0,
                'limit'  => 0,
                'filter' => [],
                'select' => [],
                'order'  => ['ID' => 'ASC'],
            ], $params
        );

        $params['filter'] = array_merge(
            $params['filter'],
            [
                'IBLOCK_ID'         => $iblockId,
                'CHECK_PERMISSIONS' => 'N',
            ]
        );

        $navParams = false;
        if ($params['limit'] > 0) {
            if ($params['offset'] > 0) {
                $pageNum = (int)floor($params['offset'] / $params['limit']) + 1;
                $navParams = [
                    'nPageSize'       => $params['limit'],
                    'iNumPage'        => $pageNum,
                    'checkOutOfRange' => true,
                ];
            } else {
                $navParams = [
                    'nTopCount' => $params['limit'],
                ];
            }
        }

        return CIBlockElement::GetList(
            $params['order'],
            $params['filter'],
            false,
            $navParams,
            $params['select']
        );
    }

    /**
     * @param int   $iblockId
     * @param array $filter
     *
     * @return int
     */
    public function getElementsCount($iblockId, $filter = [])
    {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        $select = [
            'ID',
            'XML_ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
            'ACTIVE',
        ];

        return (int)CIBlockElement::GetList(
            [],
            $filter,
            [],
            false,
            $select
        );
    }

    /**
     * Сохраняет элемент инфоблока
     * Создаст если не было, обновит если существует (поиск по коду)
     *
     * @param       $iblockId
     * @param array $fields
     * @param array $props
     *
     * @throws HelperException
     * @return int|void
     */
    public function saveElement($iblockId, $fields = [], $props = [])
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $item = $this->getElement($iblockId, $fields['CODE']);
        if (!empty($item['ID'])) {
            return $this->updateElement($item['ID'], $fields, $props);
        }

        return $this->addElement($iblockId, $fields, $props);
    }

    /**
     * Обновляет элемент инфоблока
     *
     * @param       $elementId
     * @param array $fields
     * @param array $props
     *
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
                throw new HelperException($ib->LAST_ERROR);
            }
        }

        if (!empty($props)) {
            CIBlockElement::SetPropertyValuesEx($elementId, $iblockId, $props);
        }

        return $elementId;
    }

    /**
     * Добавляет элемент инфоблока
     *
     * @param       $iblockId
     * @param array $fields - поля
     * @param array $props  - свойства
     *
     * @throws HelperException
     * @return int|void
     */
    public function addElement($iblockId, $fields = [], $props = [])
    {
        $default = [
            'NAME'              => 'element',
            'IBLOCK_SECTION_ID' => false,
            'ACTIVE'            => 'Y',
            'PREVIEW_TEXT'      => '',
            'DETAIL_TEXT'       => '',
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

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * @param       $iblockId
     * @param array $fields
     * @param array $props
     *
     * @throws HelperException
     * @return int|void
     */
    public function saveElementByXmlId($iblockId, $fields = [], $props = [])
    {
        $this->checkRequiredKeys($fields, ['XML_ID']);

        $item = $this->getElement($iblockId, ['=XML_ID' => $fields['XML_ID']]);
        if (!empty($item['ID'])) {
            return $this->updateElement($item['ID'], $fields, $props);
        }

        return $this->addElement($iblockId, $fields, $props);
    }

    /**
     * Добавляет элемент инфоблока если он не существует
     *
     * @param int   $iblockId
     * @param array $fields
     * @param array $props
     *
     * @throws HelperException
     * @return bool|mixed
     */
    public function addElementIfNotExists($iblockId, $fields, $props = [])
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $item = $this->getElement($iblockId, $fields['CODE']);
        if ($item) {
            return $item['ID'];
        }

        return $this->addElement($iblockId, $fields, $props);
    }

    /**
     * Обновляет элемент инфоблока если он существует
     *
     * @param int   $iblockId
     * @param array $fields
     * @param array $props
     *
     * @throws HelperException
     * @return bool|int|void
     */
    public function updateElementIfExists($iblockId, $fields = [], $props = [])
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $item = $this->getElement($iblockId, $fields['CODE']);
        if (!$item) {
            return false;
        }

        $fields['IBLOCK_ID'] = $iblockId;
        unset($fields['CODE']);

        return $this->updateElement($item['ID'], $fields, $props);
    }

    /**
     * @throws HelperException
     */
    public function deleteElementByXmlId($iblockId, $xmlId)
    {
        if (!empty($xmlId)) {
            $item = $this->getElement($iblockId, ['=XML_ID' => $xmlId]);
            if ($item) {
                return $this->deleteElement($item['ID']);
            }
        }
        return false;
    }

    /**
     * Удаляет элемент инфоблока
     *
     * @param $elementId
     *
     * @throws HelperException
     * @return bool|void
     */
    public function deleteElement($elementId)
    {
        $ib = new CIBlockElement;
        if ($ib->Delete($elementId)) {
            return true;
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Удаляет элемент инфоблока если он существует
     *
     * @param $iblockId
     * @param $code
     *
     * @throws HelperException
     * @return bool|void
     */
    public function deleteElementIfExists($iblockId, $code)
    {
        return $this->deleteElementByCode($iblockId, $code);
    }

    /**
     * @throws HelperException
     */
    public function deleteElementByCode($iblockId, $code)
    {
        if (!empty($code)) {
            $item = $this->getElement($iblockId, ['=CODE' => $code]);
            if ($item) {
                return $this->deleteElement($item['ID']);
            }
        }
        return false;
    }

    /**
     * @param $iblockId
     * @param $elementId
     *
     * @throws HelperException
     * @return array
     */
    public function getElementUniqFilterById($iblockId, $elementId)
    {
        if (empty($elementId)) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_IB_ELEMENT_ID_EMPTY',
                    [
                        '#IBLOCK_ID#' => $iblockId,
                    ]
                )
            );
        }

        $element = $this->getElement($iblockId, ['ID' => $elementId]);

        if (empty($element['ID'])) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_IB_ELEMENT_ID_NOT_FOUND',
                    [
                        '#IBLOCK_ID#'  => $iblockId,
                        '#ELEMENT_ID#' => $elementId,
                    ]
                )
            );
        }

        return [
            'NAME'   => $element['NAME'],
            'XML_ID' => $element['XML_ID'],
            'CODE'   => $element['CODE'],
        ];
    }

    /**
     * @throws HelperException
     */
    public function getElementIdByUniqFilter($iblockId, $uniqFilter)
    {
        if (empty($uniqFilter)) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_IB_ELEMENT_ID_EMPTY',
                    [
                        '#IBLOCK_ID#' => $iblockId,
                    ]
                )
            );
        }

        $uniqFilter['IBLOCK_ID'] = $iblockId;

        $element = $this->getElement($iblockId, $uniqFilter);

        if (empty($element['ID'])) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_IB_ELEMENT_BY_FILTER_NOT_FOUND',
                    [
                        '#IBLOCK_ID#' => $iblockId,
                        '#NAME#'      => $uniqFilter['NAME'],
                    ]
                )
            );
        }

        return $element['ID'];
    }
}
