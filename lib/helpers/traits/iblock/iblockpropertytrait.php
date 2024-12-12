<?php

namespace Sprint\Migration\Helpers\Traits\Iblock;

use Bitrix\Iblock\Model\PropertyFeature;
use Bitrix\Iblock\PropertyFeatureTable;
use Bitrix\Iblock\SectionPropertyTable;
use CIBlockProperty;
use CIBlockPropertyEnum;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;

trait IblockPropertyTrait
{
    /**
     * Сохраняет свойство инфоблока
     * Создаст если не было, обновит если существует и отличается
     *
     * @param       $iblockId
     * @param array $fields
     *
     * @throws HelperException
     * @return bool|mixed
     */
    public function saveProperty($iblockId, $fields)
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $exists = $this->getProperty($iblockId, $fields['CODE']);
        $fields = $this->prepareExportProperty($fields);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->addProperty($iblockId, $fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'IB_PROPERTY_CREATED',
                    [
                        '#IBLOCK_ID#' => $iblockId,
                        '#NAME#'      => $fields['CODE'],
                    ]
                )
            );

            return $ok;
        }

        try {
            $exportExists = $this->prepareExportProperty($exists);
        } catch (HelperException $e) {
            $exportExists = [];
        }

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->getMode('test')
                ? true
                : $this->updatePropertyById(
                    $exists['ID'],
                    array_merge($fields, ['IBLOCK_ID' => $iblockId])
                );
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'IB_PROPERTY_UPDATED',
                    [
                        '#IBLOCK_ID#' => $iblockId,
                        '#NAME#'      => $fields['CODE'],
                    ]
                )
            );
            $this->outDiffIf($ok, $exportExists, $fields);

            return $ok;
        }

        return $this->getMode('test') ? true : $exists['ID'];
    }

    /**
     * Получает свойство инфоблока
     *
     * @param $iblockId
     * @param $code int|array - код или фильтр
     *
     * @return array|bool
     */
    public function getProperty($iblockId, $code)
    {
        /** @compatibility filter or code */
        $filter = is_array($code)
            ? $code
            : [
                'CODE' => $code,
            ];

        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';
        /* do not use =CODE in filter */
        $property = CIBlockProperty::GetList(['SORT' => 'ASC'], $filter)->Fetch();
        return $this->prepareProperty($property);
    }

    protected function prepareProperty($property)
    {
        if (!empty($property['ID']) && !empty($property['IBLOCK_ID'])) {
            if ($property['PROPERTY_TYPE'] == 'L') {
                $property['VALUES'] = $this->getPropertyEnums(
                    [
                        'IBLOCK_ID'   => $property['IBLOCK_ID'],
                        'PROPERTY_ID' => $property['ID'],
                    ]
                );
            }

            $features = $this->getPropertyFeatures($property['ID']);
            if (!empty($features)) {
                $property['FEATURES'] = $features;
            }

            $sectionProperty = $this->getSectionProperty($property['ID']);
            if (!empty($sectionProperty)) {
                $property = array_merge($property, $sectionProperty);
            }
        }

        return $property;
    }

    /**
     * Получает значения списков для свойств инфоблоков
     *
     * @param array $filter
     *
     * @return array
     */
    public function getPropertyEnums($filter = [])
    {
        $result = [];
        $dbres = CIBlockPropertyEnum::GetList(
            [
                'SORT'  => 'ASC',
                'VALUE' => 'ASC',
            ], $filter
        );
        while ($item = $dbres->Fetch()) {
            $result[] = $item;
        }
        return $result;
    }

    /**
     * @throws HelperException
     */
    public function getPropertyFeatures($propertyId)
    {
        if (!class_exists('\Bitrix\Iblock\Model\PropertyFeature')
            || !class_exists('\Bitrix\Iblock\PropertyFeatureTable')) {
            return [];
        }

        $features = [];
        try {
            if (PropertyFeature::isEnabledFeatures()) {
                $features = PropertyFeatureTable::getList([
                    'select' => ['ID', 'MODULE_ID', 'FEATURE_ID', 'IS_ENABLED'],
                    'filter' => ['=PROPERTY_ID' => (int)$propertyId],
                ])->fetchAll();
            }
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }

        return $features;
    }

    public function getSectionProperty($propertyId)
    {
        try {
            $link = SectionPropertyTable::getList([
                'filter' => [
                    'PROPERTY_ID' => $propertyId,
                ],
            ])->fetch();

            return $link ? [
                'SMART_FILTER'     => $link['SMART_FILTER'],
                'DISPLAY_TYPE'     => $link['DISPLAY_TYPE'],
                'DISPLAY_EXPANDED' => $link['DISPLAY_EXPANDED'],
                'FILTER_HINT'      => $link['FILTER_HINT'],
            ] : [];
        } catch (Exception $e) {
        }
        return [];
    }

    /**
     * @throws HelperException
     */
    protected function prepareExportProperty($prop)
    {
        if (empty($prop)) {
            return $prop;
        }

        if (!empty($prop['VALUES']) && is_array($prop['VALUES'])) {
            $exportValues = [];

            foreach ($prop['VALUES'] as $item) {
                $exportValues[] = [
                    'VALUE'  => $item['VALUE'],
                    'DEF'    => $item['DEF'],
                    'SORT'   => $item['SORT'],
                    'XML_ID' => $item['XML_ID'],
                ];
            }

            $prop['VALUES'] = $exportValues;
        }

        if (!empty($prop['FEATURES']) && is_array($prop['FEATURES'])) {
            $exportFeatures = [];
            foreach ($prop['FEATURES'] as $item) {
                $exportFeatures[] = [
                    'MODULE_ID'  => $item['MODULE_ID'],
                    'FEATURE_ID' => $item['FEATURE_ID'],
                    'IS_ENABLED' => $item['IS_ENABLED'],
                ];
            }

            $prop['FEATURES'] = $exportFeatures;
        }

        if (!empty($prop['LINK_IBLOCK_ID'])) {
            $prop['LINK_IBLOCK_ID'] = $this->getIblockUid($prop['LINK_IBLOCK_ID']);
        }

        unset($prop['ID']);
        unset($prop['IBLOCK_ID']);
        unset($prop['TIMESTAMP_X']);
        unset($prop['TMP_ID']);

        return $prop;
    }

    /**
     * Добавляет свойство инфоблока
     *
     * @param $iblockId
     * @param $fields
     *
     * @throws HelperException
     * @return int|void
     */
    public function addProperty($iblockId, $fields)
    {
        $default = [
            'NAME'           => '',
            'ACTIVE'         => 'Y',
            'SORT'           => '500',
            'CODE'           => '',
            'PROPERTY_TYPE'  => 'S',
            'USER_TYPE'      => '',
            'ROW_COUNT'      => '1',
            'COL_COUNT'      => '30',
            'LIST_TYPE'      => 'L',
            'MULTIPLE'       => 'N',
            'IS_REQUIRED'    => 'N',
            'FILTRABLE'      => 'Y',
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
            [$ptype, $utype] = explode(':', $fields['PROPERTY_TYPE']);
            $fields['PROPERTY_TYPE'] = $ptype;
            $fields['USER_TYPE'] = $utype;
        }

        if (false !== strpos($fields['LINK_IBLOCK_ID'], ':')) {
            $fields['LINK_IBLOCK_ID'] = $this->getIblockIdByUid($fields['LINK_IBLOCK_ID']);
        }

        $fields['IBLOCK_ID'] = $iblockId;

        $ib = new CIBlockProperty;
        $propertyId = $ib->Add($fields);

        if ($propertyId) {
            return $propertyId;
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Обновляет свойство инфоблока
     *
     * @param $propertyId
     * @param $fields
     *
     * @throws HelperException
     * @return int|void
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
            [$ptype, $utype] = explode(':', $fields['PROPERTY_TYPE']);
            $fields['PROPERTY_TYPE'] = $ptype;
            $fields['USER_TYPE'] = $utype;
        }

        if (false !== strpos($fields['LINK_IBLOCK_ID'], ':')) {
            $fields['LINK_IBLOCK_ID'] = $this->getIblockIdByUid($fields['LINK_IBLOCK_ID']);
        }

        if (isset($fields['VALUES']) && is_array($fields['VALUES'])) {
            $existsEnums = $this->getPropertyEnums(
                [
                    'PROPERTY_ID' => $propertyId,
                ]
            );

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

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * Получает значения списков для свойства инфоблока
     *
     * @param $iblockId
     * @param $propertyId
     *
     * @return array
     */
    public function getPropertyEnumValues($iblockId, $propertyId)
    {
        return $this->getPropertyEnums(
            [
                'IBLOCK_ID'   => $iblockId,
                'PROPERTY_ID' => $propertyId,
            ]
        );
    }

    /**
     * Получает свойство инфоблока
     *
     * @param $iblockId
     * @param $code int|array - код или фильтр
     *
     * @return int
     */
    public function getPropertyId($iblockId, $code)
    {
        $item = $this->getProperty($iblockId, $code);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    /**
     * Добавляет свойство инфоблока если его не существует
     *
     * @param int   $iblockId
     * @param array $fields
     *
     * @throws HelperException
     * @return bool
     */
    public function addPropertyIfNotExists($iblockId, $fields)
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $property = $this->getProperty($iblockId, $fields['CODE']);
        if ($property) {
            return $property['ID'];
        }

        return $this->addProperty($iblockId, $fields);
    }

    /**
     * Получает свойство инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param      $iblockId
     * @param bool $code
     *
     * @throws HelperException
     * @return array|void
     */
    public function exportProperty($iblockId, $code = false)
    {
        $export = $this->prepareExportProperty(
            $this->getProperty($iblockId, $code)
        );

        if (!empty($export['CODE'])) {
            return $export;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_IB_PROPERTY_CODE_NOT_FOUND'
            )
        );
    }

    /**
     * Получает свойства инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     */
    public function exportProperties(int $iblockId, array $filter = []): array
    {
        $exports = [];
        $items = $this->getProperties($iblockId, $filter);
        foreach ($items as $item) {
            if (!empty($item['CODE'])) {
                if (!empty($item['LINK_IBLOCK_ID'])) {
                    try {
                        $item['LINK_IBLOCK_ID'] = $this->getIblockUid($item['LINK_IBLOCK_ID']);
                    } catch (HelperException $e) {
                        continue;
                    }
                }
                $exports[] = $this->prepareExportProperty($item);
            }
        }
        return $exports;
    }

    public function getProperties(int $iblockId, array $filter = []): array
    {
        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        $filterIds = false;
        if (isset($filter['ID']) && is_array($filter['ID'])) {
            $filterIds = $filter['ID'];
            unset($filter['ID']);
        }

        $dbres = CIBlockProperty::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], $filter);

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
     * @param $iblockId
     * @param $code
     *
     * @throws HelperException
     * @return bool
     * @deprecated
     */
    public function deleteProperty($iblockId, $code)
    {
        return $this->deletePropertyIfExists($iblockId, $code);
    }

    /**
     * Удаляет свойство инфоблока если оно существует
     *
     * @param $iblockId
     * @param $code
     *
     * @throws HelperException
     * @return bool|void
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
     *
     * @param $propertyId
     *
     * @throws HelperException
     * @return bool|void
     */
    public function deletePropertyById($propertyId)
    {
        $ib = new CIBlockProperty;
        if ($ib->Delete($propertyId)) {
            return true;
        }

        throw new HelperException($ib->LAST_ERROR);
    }

    /**
     * @param $iblockId
     * @param $code
     * @param $fields
     *
     * @throws HelperException
     * @return bool|mixed
     * @deprecated
     */
    public function updateProperty($iblockId, $code, $fields)
    {
        return $this->updatePropertyIfExists($iblockId, $code, $fields);
    }

    /**
     * Обновляет свойство инфоблока если оно существует
     *
     * @param $iblockId
     * @param $code
     * @param $fields
     *
     * @throws HelperException
     * @return bool|int|void
     */
    public function updatePropertyIfExists($iblockId, $code, $fields)
    {
        $property = $this->getProperty($iblockId, $code);
        if (!$property) {
            return false;
        }

        return $this->updatePropertyById(
            $property['ID'],
            array_merge($fields, ['IBLOCK_ID' => $iblockId])
        );
    }

    public function getPropertyType($iblockId, $code)
    {
        $prop = $this->getProperty($iblockId, $code);
        return $prop['PROPERTY_TYPE'];
    }

    public function getPropertyLinkIblockId($iblockId, $code)
    {
        $prop = $this->getProperty($iblockId, $code);
        return $prop['LINK_IBLOCK_ID'];
    }

    public function isPropertyMultiple($iblockId, $code)
    {
        $prop = $this->getProperty($iblockId, $code);
        return ($prop['MULTIPLE'] == 'Y');
    }

    public function getPropertyEnumIdByXmlId($iblockId, $code, $xmlId)
    {
        $prop = $this->getProperty($iblockId, $code);
        if (empty($prop['VALUES']) || !is_array($prop['VALUES'])) {
            return '';
        }

        foreach ($prop['VALUES'] as $val) {
            if ($val['XML_ID'] == $xmlId) {
                return $val['ID'];
            }
        }
        return '';
    }
}
