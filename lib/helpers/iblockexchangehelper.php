<?php

namespace Sprint\Migration\Helpers;

use _CIBElement;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\Base\ExchangeDto;

class IblockExchangeHelper extends IblockHelper
{
    protected array $cachedProps = [];

    public function getProperty($iblockId, $code)
    {
        $key = $iblockId . $code;

        if (!isset($this->cachedProps[$key])) {
            $this->cachedProps[$key] = parent::getProperty($iblockId, $code);
        }
        return $this->cachedProps[$key];
    }

    /**
     * Структура инфоблоков для построения выпадающего списка
     *
     * @return array
     */
    public function getIblocksStructure(): array
    {
        return $this->createIblocksStructure(
            $this->getIblockTypes(),
            $this->getIblocks()
        );
    }

    public function createIblocksStructure(array $iblockTypes, array $iblocks): array
    {
        $res = [];
        foreach ($iblockTypes as $iblockType) {
            $res[$iblockType['ID']] = [
                'title' => '[' . $iblockType['ID'] . '] ' . $iblockType['LANG'][LANGUAGE_ID]['NAME'],
                'items' => [],
            ];
        }

        foreach ($iblocks as $iblock) {
            if (!empty($iblock['CODE'])) {
                $res[$iblock['IBLOCK_TYPE_ID']]['items'][] = [
                    'title' => '[' . $iblock['CODE'] . '] ' . $iblock['NAME'],
                    'value' => $iblock['ID'],
                ];
            }
        }

        return $res;
    }

    /**
     * @param $iblockId
     *
     * @return array
     */
    public function getIblockPropertiesStructure($iblockId): array
    {
        $props = $this->exportProperties($iblockId);

        $res = [];
        foreach ($props as $prop) {
            $res[] = [
                'title' => '[' . $prop['CODE'] . '] ' . $prop['NAME'],
                'value' => $prop['CODE'],
            ];
        }
        return $res;
    }

    /**
     * @param $iblockId
     *
     * @return array
     */
    public function getIblockElementFieldsStructure($iblockId): array
    {
        $fields = $this->exportIblockElementFields($iblockId);

        $res = [];
        foreach ($fields as $fieldName => $field) {
            $res[] = [
                'title' => '[' . $fieldName . '] ' . $field['NAME'],
                'value' => $fieldName,
            ];
        }
        return $res;
    }

    /**
     * @throws HelperException
     */
    public function getSectionIdByUniqName($iblockId, $uniqName)
    {
        if (is_numeric($uniqName)) {
            return $uniqName;
        }

        if (is_string($uniqName)) {
            [$sectionName, $depthLevel, $code] = explode('|', $uniqName);
            $uniqName = [];
            if ($sectionName) {
                $uniqName['NAME'] = $sectionName;
            }
            if ($depthLevel) {
                $uniqName['DEPTH_LEVEL'] = $depthLevel;
            }
            if ($code) {
                $uniqName['CODE'] = $code;
            }
        }

        return $this->getSectionIdByUniqFilter($iblockId, $uniqName);
    }

    /**
     * @throws HelperException
     */
    public function getElementIdByUniqName($iblockId, $uniqName)
    {
        if (is_numeric($uniqName)) {
            return $uniqName;
        }

        if (is_string($uniqName)) {
            [$elementName, $xmlId, $code] = explode('|', $uniqName);
            $uniqName = [];
            if ($elementName) {
                $uniqName['NAME'] = $elementName;
            }
            if ($xmlId) {
                $uniqName['XML_ID'] = $xmlId;
            }
            if ($code) {
                $uniqName['CODE'] = $code;
            }
        }

        return $this->getElementIdByUniqFilter($iblockId, $uniqName);
    }

    /**
     * @throws HelperException
     */
    public function getSectionUniqNameById($iblockId, $sectionId): string
    {
        $filter = $this->getSectionUniqFilterById($iblockId, $sectionId);
        return $filter['NAME'] . '|' . $filter['DEPTH_LEVEL'] . '|' . $filter['CODE'];
    }

    /**
     * @throws HelperException
     */
    public function getSectionUniqNamesByIds($iblockId, $sectionIds): array
    {
        $sectionIds = array_filter(is_array($sectionIds) ? $sectionIds : [$sectionIds]);

        $uniqNames = [];
        foreach ($sectionIds as $sectionId) {
            $uniqNames[] = $this->getSectionUniqNameById($iblockId, $sectionId);
        }
        return $uniqNames;
    }

    /**
     * @throws HelperException
     */
    public function getElementUniqNameById($iblockId, $elementId): string
    {
        $filter = $this->getElementUniqFilterById($iblockId, $elementId);
        return $filter['NAME'] . '|' . $filter['XML_ID'] . '|' . $filter['CODE'];
    }

    /**
     * @throws HelperException
     */
    public function getElementUniqNamesByIds($iblockId, $elementIds): array
    {
        $elementIds = array_filter(is_array($elementIds) ? $elementIds : [$elementIds]);

        $uniqNames = [];
        foreach ($elementIds as $elementId) {
            $uniqNames[] = $this->getElementUniqNameById($iblockId, $elementId);
        }
        return $uniqNames;
    }

    public function getElementFields(_CIBElement $element)
    {
        $fields = $element->GetFields();
        $fields['IBLOCK_SECTION'] = $this->getElementSectionIds($fields['ID']);
        return $fields;
    }

    public function getElementProps(_CIBElement $element): array
    {
        return $element->GetProperties();
    }

    /**
     * @throws HelperException
     */
    public function getElementsListExchangeDto($iblockId, $params = [], $exportFields = [], $exportProperties = []): ExchangeDto
    {
        $dbres = $this->getElementsList($iblockId, $params);

        $dto = new ExchangeDto('tmp');
        while ($element = $dbres->GetNextElement(false, false)) {
            $dto->addChild(
                $this->createRecordDto(
                    $iblockId,
                    [
                        'FIELDS' => $this->getElementFields($element),
                        'PROPS' => $this->getElementProps($element),
                    ],
                    $exportFields,
                    $exportProperties
                ));
        }
        return $dto;
    }

    /**
     * @throws HelperException
     */
    protected function createRecordDto($iblockId, array $element, array $exportFields, array $exportProperties): ExchangeDto
    {
        $item = new ExchangeDto('item');

        foreach ($element['FIELDS'] as $code => $val) {
            if (in_array($code, $exportFields)) {
                $item->addChild(
                    $this->createFieldDto([
                        'NAME' => $code,
                        'VALUE' => $val,
                        'IBLOCK_ID' => $iblockId
                    ])
                );
            }
        }

        foreach ($element['PROPS'] as $prop) {
            if (in_array($prop['CODE'], $exportProperties)) {
                $item->addChild(
                    $this->createPropertyDto($prop)
                );
            }
        }

        return $item;
    }

    /**
     * @throws HelperException
     */
    protected function createFieldDto(array $field): ExchangeDto
    {
        $dto = new ExchangeDto('field', ['name' => $field['NAME']]);
        if ($field['NAME'] == 'PREVIEW_PICTURE') {
            $dto->addFile($field['VALUE']);
        } elseif ($field['NAME'] == 'DETAIL_PICTURE') {
            $dto->addFile($field['VALUE']);
        } elseif ($field['NAME'] == 'IBLOCK_SECTION') {
            $uniqSections = $this->getSectionUniqNamesByIds($field['IBLOCK_ID'], $field['VALUE']);
            $dto->addValue($uniqSections);
        } else {
            $dto->addValue($field['VALUE']);
        }
        return $dto;
    }

    /**
     * @throws HelperException
     */
    protected function createPropertyDto(array $prop): ExchangeDto
    {
        $dto = new ExchangeDto('property', ['name' => $prop['CODE']]);

        if ($prop['PROPERTY_TYPE'] == 'F') {
            $this->addPropertyValueFile($dto, $prop);
        } elseif ($prop['PROPERTY_TYPE'] == 'L') {
            $this->addPropertyValueList($dto, $prop);
        } elseif ($prop['PROPERTY_TYPE'] == 'G') {
            $this->addPropertyValueSection($dto, $prop);
        } elseif ($prop['PROPERTY_TYPE'] == 'E') {
            $this->addPropertyValueElement($dto, $prop);
        } else {
            $this->addPropertyValueString($dto, $prop);
        }
        return $dto;
    }

    protected function addPropertyValueString(ExchangeDto $dto, $prop): void
    {
        if ($prop['MULTIPLE'] == 'Y') {
            foreach ($prop['VALUE'] as $index => $val1) {
                $dto->addValue($val1, ['description' => $prop['DESCRIPTION'][$index] ?? '']);
            }
        } else {
            $dto->addValue($prop['VALUE'], ['description' => $prop['DESCRIPTION']]);
        }
    }

    /**
     * @throws HelperException
     */
    protected function addPropertyValueSection(ExchangeDto $dto, $prop): void
    {
        $uniqNames = $this->getSectionUniqNamesByIds($prop['LINK_IBLOCK_ID'], $prop['VALUE']);
        $dto->addValue($uniqNames);
    }

    /**
     * @throws HelperException
     */
    protected function addPropertyValueElement(ExchangeDto $dto, $prop): void
    {
        $uniqNames = $this->getElementUniqNamesByIds($prop['LINK_IBLOCK_ID'], $prop['VALUE']);
        $dto->addValue($uniqNames);
    }

    protected function addPropertyValueList(ExchangeDto $dto, $prop): void
    {
        $dto->addValue($prop['VALUE_XML_ID']);
    }


    protected function addPropertyValueFile(ExchangeDto $dto, $prop): void
    {
        $dto->addFile($prop['VALUE']);
    }
}
