<?php

namespace Sprint\Migration\Helpers\Traits\Iblock;

use Bitrix\Iblock\Helpers\Admin\Property;
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
    public function getPropertyTypes(): array
    {
        $baseTypes = Property::getBaseTypeList(true);
        $userTypes = CIBlockProperty::GetUserType();

        $items = [];
        foreach ($baseTypes as $id => $title) {
            $items[] = ['ID' => $id, 'TITLE' => $title];
        }
        foreach ($userTypes as $item) {
            $id = $item['PROPERTY_TYPE'] . ':' . $item['USER_TYPE'];
            $items[] = ['ID' => $id, 'TITLE' => $item['DESCRIPTION'], 'GROUP' => $item['PROPERTY_TYPE']];
        }
        return $items;
    }

    /**
     * Сохраняет свойство инфоблока.
     * Создаст если не было, обновит если существует и отличается
     *
     * @throws HelperException
     */
    public function saveProperty(int $iblockId, array $fields)
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $exists = $this->getProperty($iblockId, $fields['CODE']);

        $fields = $this->prepareExportProperty($fields);

        if (empty($exists)) {
            return $this->addProperty($iblockId, $fields);
        }

        try {
            $exportExists = $this->prepareExportProperty($exists);
        } catch (HelperException) {
            $exportExists = [];
        }

        if ($this->checkDiff($exportExists, $fields)) {
            return $this->updatePropertyById(
                $exists['ID'],
                array_merge($fields, ['IBLOCK_ID' => $iblockId]),
            );
        }

        return $exists['ID'];
    }

    /**
     * Получает свойство инфоблока
     *
     * @throws HelperException
     */
    public function getProperty(int $iblockId, array|string $code): bool|array
    {
        /* do not use =CODE in filter */

        $filter = is_array($code) ? $code : ['CODE' => $code];

        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        $property = CIBlockProperty::GetList(['SORT' => 'ASC'], $filter)->Fetch();

        return $property ? $this->prepareProperty($property) : false;
    }

    /**
     * @throws HelperException
     */
    protected function prepareProperty(array $property): array
    {
        if (!empty($property['ID']) && !empty($property['IBLOCK_ID'])) {
            if ($property['PROPERTY_TYPE'] == 'L') {
                $property['VALUES'] = $this->getPropertyEnums(
                    [
                        'IBLOCK_ID'   => $property['IBLOCK_ID'],
                        'PROPERTY_ID' => $property['ID'],
                    ],
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
     */
    public function getPropertyEnums(array $filter = []): array
    {
        $dbres = CIBlockPropertyEnum::GetList(
            [
                'SORT'  => 'ASC',
                'VALUE' => 'ASC',
            ],
            $filter,
        );
        return $this->fetchAll($dbres);
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
                    'select' => [
                        'ID',
                        'MODULE_ID',
                        'FEATURE_ID',
                        'IS_ENABLED',
                    ],
                    'filter' => ['=PROPERTY_ID' => (int) $propertyId],
                ])->fetchAll();
            }
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }

        return $features;
    }

    /**
     * @throws HelperException
     */
    public function getSectionProperty(int $propertyId): array
    {
        try {
            $link = SectionPropertyTable::getList([
                'filter' => [
                    'PROPERTY_ID' => $propertyId,
                    'SECTION_ID'  => 0,
                ],
            ])->fetch();

            return $link
                ? [
                    'SECTION_PROPERTY' => 'Y',
                    'SMART_FILTER'     => $link['SMART_FILTER'],
                    'DISPLAY_TYPE'     => $link['DISPLAY_TYPE'],
                    'DISPLAY_EXPANDED' => $link['DISPLAY_EXPANDED'],
                    'FILTER_HINT'      => $link['FILTER_HINT'],
                ]
                : [
                    'SECTION_PROPERTY' => 'N',
                ];
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    protected function prepareExportProperty(array $prop): array
    {
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

        if (!empty($prop['LINK_IBLOCK_ID']) && is_numeric($prop['LINK_IBLOCK_ID'])) {
            $prop['LINK_IBLOCK_ID'] = $this->getIblockUid($prop['LINK_IBLOCK_ID']);
        }

        $this->unsetKeys($prop, [
            'ID',
            'IBLOCK_ID',
            'TIMESTAMP_X',
            'TMP_ID',
        ]);

        return $prop;
    }

    /**
     * Добавляет свойство инфоблока
     *
     * @throws HelperException
     */
    public function addProperty(int $iblockId, array $fields): int
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

        if (str_contains($fields['PROPERTY_TYPE'], ':')) {
            [$ptype, $utype] = explode(':', $fields['PROPERTY_TYPE'], 2);
            $fields['PROPERTY_TYPE'] = $ptype;
            $fields['USER_TYPE'] = $utype;
        }

        if (str_contains($fields['LINK_IBLOCK_ID'], ':')) {
            $fields['LINK_IBLOCK_ID'] = $this->getIblockIdByUid($fields['LINK_IBLOCK_ID']);
        }

        $fields['IBLOCK_ID'] = $iblockId;

        $ib = new CIBlockProperty();
        $propertyId = $ib->Add($fields);

        if ($propertyId) {
            $this->outNotice(Locale::getMessage('IB_PROPERTY_CREATED', [
                '#IBLOCK_ID#' => $iblockId,
                '#NAME#'      => $fields['CODE'],
            ]));
            return (int) $propertyId;
        }

        throw new HelperException(
            $ib->LAST_ERROR . " in " . var_export([
                'NAME' => $fields['NAME'],
                'CODE' => $fields['CODE'],
            ], true),
        );
    }

    /**
     * Обновляет свойство инфоблока
     *
     * @throws HelperException
     */
    public function updatePropertyById(int $propertyId, array $fields): int
    {
        if (!empty($fields['VALUES']) && !isset($fields['PROPERTY_TYPE'])) {
            $fields['PROPERTY_TYPE'] = 'L';
        }

        if (!empty($fields['LINK_IBLOCK_ID']) && !isset($fields['PROPERTY_TYPE'])) {
            $fields['PROPERTY_TYPE'] = 'E';
        }

        if (str_contains($fields['PROPERTY_TYPE'], ':')) {
            [$ptype, $utype] = explode(':', $fields['PROPERTY_TYPE'], 2);
            $fields['PROPERTY_TYPE'] = $ptype;
            $fields['USER_TYPE'] = $utype;
        }

        if (str_contains($fields['LINK_IBLOCK_ID'], ':')) {
            $fields['LINK_IBLOCK_ID'] = $this->getIblockIdByUid($fields['LINK_IBLOCK_ID']);
        }

        if (isset($fields['VALUES']) && is_array($fields['VALUES'])) {
            $existsEnums = $this->getPropertyEnums(
                [
                    'PROPERTY_ID' => $propertyId,
                ],
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
            $this->outNotice(Locale::getMessage('IB_PROPERTY_UPDATED', [
                '#IBLOCK_ID#' => $fields['IBLOCK_ID'],
                '#NAME#'      => $fields['CODE'],
            ]));

            return $propertyId;
        }

        throw new HelperException(
            $ib->LAST_ERROR . " in " . var_export([
                'ID'   => $propertyId,
                'CODE' => $fields['CODE'],
            ], true),
        );
    }

    /**
     * Получает значения списков для свойства инфоблока
     */
    public function getPropertyEnumValues(int $iblockId, int $propertyId): array
    {
        return $this->getPropertyEnums(
            [
                'IBLOCK_ID'   => $iblockId,
                'PROPERTY_ID' => $propertyId,
            ],
        );
    }

    /**
     * Добавляет свойство инфоблока если его не существует
     *
     * @throws HelperException
     */
    public function addPropertyIfNotExists(int $iblockId, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['CODE']);

        $property = $this->getProperty($iblockId, $fields['CODE']);
        if (!empty($property['ID'])) {
            return (int) $property['ID'];
        }

        return $this->addProperty($iblockId, $fields);
    }

    /**
     * Получает свойство инфоблока, данные подготовлены для экспорта в миграцию
     *
     * @throws HelperException
     */
    public function exportProperty(int $iblockId, array|string $code): array
    {
        $item = $this->getProperty($iblockId, $code);

        if (!empty($item['CODE'])) {
            return $this->prepareExportProperty($item);
        }

        throw new HelperException(Locale::getMessage('ERR_IB_PROPERTY_CODE_NOT_FOUND'));
    }

    /**
     * Получает свойства инфоблока
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @throws HelperException
     */
    public function exportProperties(int $iblockId, array $filter = []): array
    {
        $filter['IBLOCK_ID'] = $iblockId;

        return $this->exportPropertiesByFilter($filter);
    }

    /**
     * @throws HelperException
     */
    public function exportPropertiesByFilter(array $filter = []): array
    {
        $properties = array_filter($this->getPropertiesByFilter($filter), fn($item) => !empty($item['CODE']));

        return array_map(fn($item) => $this->prepareExportProperty($item), $properties);
    }

    /**
     * @throws HelperException
     */
    public function getPropertiesByFilter(array $filter = []): array
    {
        $filter['CHECK_PERMISSIONS'] = 'N';

        $propertyIds = [];
        if (isset($filter['ID']) && is_array($filter['ID'])) {
            $propertyIds = $filter['ID'];
            unset($filter['ID']);
        }

        $propertyTypes = [];
        if (isset($filter['PROPERTY_TYPE']) && is_array($filter['PROPERTY_TYPE'])) {
            $propertyTypes = $filter['PROPERTY_TYPE'];
            unset($filter['PROPERTY_TYPE']);
        }

        $dbres = CIBlockProperty::GetList(['SORT' => 'ASC', 'ID' => 'ASC'], $filter);
        $all = $this->fetchAll($dbres);

        if (!empty($propertyIds)) {
            $all = array_filter($all, fn($item) => in_array($item['ID'], $propertyIds));
        }

        if (!empty($propertyTypes)) {
            $all = array_filter($all, function ($item) use ($propertyTypes) {
                $pt = $item['PROPERTY_TYPE'] ?? '';
                $ut = $item['USER_TYPE'] ?? '';

                $pt = $ut ? "$pt:$ut" : $pt;

                return in_array($pt, $propertyTypes);
            });
        }

        return array_map(fn($item) => $this->prepareProperty($item), $all);
    }

    /**
     * @throws HelperException
     */
    public function getProperties(int $iblockId, array $filter = []): array
    {
        $filter['IBLOCK_ID'] = $iblockId;

        return $this->getPropertiesByFilter($filter);
    }

    /**
     * Удаляет свойство инфоблока если оно существует
     *
     * @throws HelperException
     */
    public function deletePropertyIfExists(int $iblockId, array|string $code): bool
    {
        $propertyId = $this->getPropertyId($iblockId, $code);
        return $propertyId && $this->deletePropertyById($propertyId);
    }

    /**
     * Получает свойство инфоблока
     *
     * @throws HelperException
     */
    public function getPropertyId(int $iblockId, array|string $code): int
    {
        $item = $this->getProperty($iblockId, $code);
        return !empty($item['ID']) ? (int) $item['ID'] : 0;
    }

    /**
     * Удаляет свойство инфоблока
     *
     * @throws HelperException
     */
    public function deletePropertyById(int $propertyId): bool
    {
        $ib = new CIBlockProperty();
        if ($ib->Delete($propertyId)) {
            return true;
        }

        throw new HelperException(
            $ib->LAST_ERROR . " in " . var_export([
                'ID'   => $propertyId,
            ], true),
        );
    }

    /**
     * Обновляет свойство инфоблока если оно существует
     *
     * @throws HelperException
     */
    public function updatePropertyIfExists(int $iblockId, array|string $code, array $fields): int
    {
        $propertyId = $this->getPropertyId($iblockId, $code);
        if ($propertyId) {
            $fields['IBLOCK_ID'] = $iblockId;
            return $this->updatePropertyById($propertyId, $fields);
        }

        return 0;
    }

    /**
     * @throws HelperException
     */
    public function getPropertyType(int $iblockId, string $code): string
    {
        $prop = $this->getProperty($iblockId, $code);
        return (string) $prop['PROPERTY_TYPE'];
    }

    /**
     * @throws HelperException
     */
    public function getPropertyUserType(int $iblockId, string $code): string
    {
        $prop = $this->getProperty($iblockId, $code);
        return (string) $prop['USER_TYPE'];
    }

    /**
     * @throws HelperException
     */
    public function getPropertyLinkIblockId(int $iblockId, string $code): int
    {
        $prop = $this->getProperty($iblockId, $code);
        return (int) $prop['LINK_IBLOCK_ID'];
    }

    /**
     * @throws HelperException
     */
    public function isPropertyMultiple(int $iblockId, string $code): bool
    {
        $prop = $this->getProperty($iblockId, $code);
        return ($prop['MULTIPLE'] == 'Y');
    }

    /**
     * @throws HelperException
     */
    public function getPropertyEnumIdByXmlId(int $iblockId, string $code, string|int $xmlId)
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
