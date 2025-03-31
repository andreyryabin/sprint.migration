<?php

namespace Sprint\Migration\Helpers\Traits\Iblock;

use Bitrix\Iblock\InheritedProperty\ElementTemplates as IpropertyTemplates;
use CIBlockElement;
use CIBlockResult;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;

trait IblockElementTrait
{
    /**
     * Получает id элемента инфоблока
     */
    public function getElementId(int $iblockId, array|string $code): int
    {
        $item = $this->getElement($iblockId, $code);
        return (int)($item['ID'] ?? 0);
    }

    /**
     * Получает элемент инфоблока
     */
    public function getElement(int $iblockId, array|string $code, array $select = []): bool|array
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
            'limit' => 1,
        ])->Fetch();

        return $item ? $this->prepareElement($item) : false;
    }

    /**
     */
    protected function prepareElement(array $item): array
    {
        $item['IBLOCK_SECTION'] = $this->getElementSectionIds($item['ID']);

        $item['IPROPERTY_TEMPLATES'] = $this->getElementIpropertyTemplates($item['IBLOCK_ID'], $item['ID']);

        return $item;
    }

    public function getElementSectionIds(int $elementId): array
    {
        $sections = $this->getElementSections($elementId);
        return array_column($sections, 'ID');
    }

    public function getElementIpropertyTemplates(int $iblockId, int $elementId): array
    {
        $templates = (new IpropertyTemplates($iblockId, $elementId))->findTemplates();
        return array_column($templates, 'TEMPLATE', 'CODE');
    }

    public function getElementSections(int $elementId, array $sectionSelect = []): array
    {
        $dbres = CIBlockElement::GetElementGroups($elementId, true, $sectionSelect);
        return $this->fetchAll($dbres);
    }

    /**
     * Получает элементы инфоблока
     */
    public function getElements(int $iblockId, array $filter = [], array $select = []): array
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

    public function getElementsList(int $iblockId, array $params = []): CIBlockResult
    {
        $params = array_merge(
            [
                'offset' => 0,
                'limit' => 0,
                'filter' => [],
                'select' => [],
                'order' => ['ID' => 'ASC'],
            ], $params
        );

        $params['filter'] = array_merge(
            $params['filter'],
            [
                'IBLOCK_ID' => $iblockId,
                'CHECK_PERMISSIONS' => 'N',
            ]
        );

        $navParams = false;
        if ($params['limit'] > 0) {
            if ($params['offset'] > 0) {
                $pageNum = (int)floor($params['offset'] / $params['limit']) + 1;
                $navParams = [
                    'nPageSize' => $params['limit'],
                    'iNumPage' => $pageNum,
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

    public function getElementsCount(int $iblockId, array $filter = []): int
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
     * Сохраняет элемент инфоблока.
     * Создаст элемент если не было, обновит если существует (поиск по коду)
     * @throws HelperException
     */
    public function saveElement(int $iblockId, array $fields = [], array $props = []): int
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
     * @throws HelperException
     */
    public function updateElement(int $elementId, array $fields = [], array $props = []): int
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
     * @throws HelperException
     */
    public function addElement(int $iblockId, array $fields = [], array $props = []): int
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
        $elementId = $ib->Add($fields);

        if ($elementId) {
            return (int)$elementId;
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * @throws HelperException
     */
    public function saveElementByXmlId(int $iblockId, array $fields = [], array $props = []): int
    {
        $this->checkRequiredKeys($fields, ['XML_ID']);

        $elementId = $this->getElementId($iblockId, ['=XML_ID' => $fields['XML_ID']]);
        if ($elementId) {
            return $this->updateElement($elementId, $fields, $props);
        }

        return $this->addElement($iblockId, $fields, $props);
    }

    /**
     * Добавляет элемент инфоблока если он не существует
     * @throws HelperException
     */
    public function addElementIfNotExists(int $iblockId, array $fields, array $props = []): int
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $elementId = $this->getElementId($iblockId, $fields['CODE']);

        return $elementId ?: $this->addElement($iblockId, $fields, $props);
    }

    /**
     * Обновляет элемент инфоблока если он существует
     *
     * @throws HelperException
     */
    public function updateElementIfExists(int $iblockId, array $fields = [], array $props = []): int
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $elementId = $this->getElementId($iblockId, $fields['CODE']);

        if ($elementId) {
            $fields['IBLOCK_ID'] = $iblockId;
            unset($fields['CODE']);
            return $this->updateElement($elementId, $fields, $props);
        }

        return 0;
    }

    /**
     * @throws HelperException
     */
    public function deleteElementByXmlId(int $iblockId, int|string $xmlId): bool
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
     * @throws HelperException
     */
    public function deleteElement(int $elementId): bool
    {
        $ib = new CIBlockElement;
        if ($ib->Delete($elementId)) {
            return true;
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Удаляет элемент инфоблока если он существует
     * @throws HelperException
     */
    public function deleteElementIfExists(int $iblockId, string $code): bool
    {
        return $this->deleteElementByCode($iblockId, $code);
    }

    /**
     * @throws HelperException
     */
    public function deleteElementByCode(int $iblockId, string $code): bool
    {
        if (!empty($code)) {
            $elementId = $this->getElementId($iblockId, ['=CODE' => $code]);
            if ($elementId) {
                return $this->deleteElement($elementId);
            }
        }
        return false;
    }

    /**
     * @throws HelperException
     */
    public function getElementUniqFilterById(int $iblockId, int $elementId): array
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
                        '#IBLOCK_ID#' => $iblockId,
                        '#ELEMENT_ID#' => $elementId,
                    ]
                )
            );
        }

        return [
            'NAME' => $element['NAME'],
            'XML_ID' => $element['XML_ID'],
            'CODE' => $element['CODE'],
        ];
    }

    /**
     * @throws HelperException
     */
    public function getElementIdByUniqFilter(int $iblockId, array $uniqFilter): int
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

        $elementId = $this->getElementId($iblockId, $uniqFilter);

        if (empty($elementId)) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_IB_ELEMENT_BY_FILTER_NOT_FOUND',
                    [
                        '#IBLOCK_ID#' => $iblockId,
                        '#NAME#' => $uniqFilter['NAME'],
                    ]
                )
            );
        }

        return $elementId;
    }
}
