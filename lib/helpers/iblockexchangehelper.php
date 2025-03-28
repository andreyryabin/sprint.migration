<?php

namespace Sprint\Migration\Helpers;

use _CIBElement;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\WriterTag;
use Sprint\Migration\Interfaces\ReaderHelperInterface;

class IblockReaderHelper extends IblockHelper implements ReaderHelperInterface
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

    //writer
    public function createAttributes(int $iblockId): array
    {
        return [
            'iblockUid' => $this->getIblockUid($iblockId)
        ];
    }

    /**
     * @throws HelperException
     */
    public function createRecordsTags(
        $iblockId,
        int $offset,
        int $limit,
        array $exportFilter,
        array $exportFields,
        array $exportProperties
    ): WriterTag
    {
        $dbres = $this->getElementsList(
            $iblockId,
            [
                'order' => ['ID' => 'ASC'],
                'offset' => $offset,
                'limit' => $limit,
                'filter' => $exportFilter,
            ]
        );

        $tag = new WriterTag('tmp');
        while ($element = $dbres->GetNextElement(false, false)) {
            $tag->addChild(
                $this->createRecordTag(
                    $iblockId,
                    $this->getElementFields($element),
                    $this->getElementProps($element),
                    $exportFields,
                    $exportProperties
                ));
        }
        return $tag;
    }

    /**
     * @throws HelperException
     */
    protected function createRecordTag(
        $iblockId,
        array $fields,
        array $props,
        array $exportFields,
        array $exportProperties
    ): WriterTag
    {
        $item = new WriterTag('item');

        foreach ($fields as $code => $val) {
            if (in_array($code, $exportFields)) {
                $item->addChild(
                    $this->createFieldTag([
                        'NAME' => $code,
                        'VALUE' => $val,
                        'IBLOCK_ID' => $iblockId
                    ])
                );
            }
        }

        foreach ($props as $prop) {
            if (in_array($prop['CODE'], $exportProperties)) {
                $item->addChild(
                    $this->createPropertyTag($prop)
                );
            }
        }

        return $item;
    }

    /**
     * @throws HelperException
     */
    protected function createFieldTag(array $field): WriterTag
    {
        $tag = new WriterTag('field', ['name' => $field['NAME']]);

        if ($field['NAME'] == 'PREVIEW_PICTURE') {
            $tag->addFile($field['VALUE']);
        } elseif ($field['NAME'] == 'DETAIL_PICTURE') {
            $tag->addFile($field['VALUE']);
        } elseif ($field['NAME'] == 'IBLOCK_SECTION') {
            $uniqSections = $this->getSectionUniqNamesByIds(
                $field['IBLOCK_ID'],
                $field['VALUE']
            );
            $tag->addValue($uniqSections);
        } else {
            $tag->addValue($field['VALUE']);
        }
        return $tag;
    }

    /**
     * @throws HelperException
     */
    protected function createPropertyTag(array $prop): WriterTag
    {
        $tag = new WriterTag('property', ['name' => $prop['CODE']]);

        if ($prop['PROPERTY_TYPE'] == 'F') {
            $this->addPropertyValueFile($tag, $prop);
        } elseif ($prop['PROPERTY_TYPE'] == 'L') {
            $this->addPropertyValueList($tag, $prop);
        } elseif ($prop['PROPERTY_TYPE'] == 'G') {
            $this->addPropertyValueSection($tag, $prop);
        } elseif ($prop['PROPERTY_TYPE'] == 'E') {
            $this->addPropertyValueElement($tag, $prop);
        } else {
            $this->addPropertyValueString($tag, $prop);
        }
        return $tag;
    }

    protected function addPropertyValueString(WriterTag $tag, $prop): void
    {
        if ($prop['MULTIPLE'] == 'Y') {
            foreach ($prop['VALUE'] as $index => $val1) {
                $tag->addValue($val1, ['description' => $prop['DESCRIPTION'][$index] ?? '']);
            }
        } else {
            $tag->addValue($prop['VALUE'], ['description' => $prop['DESCRIPTION']]);
        }
    }

    /**
     * @throws HelperException
     */
    protected function addPropertyValueSection(WriterTag $tag, $prop): void
    {
        $uniqNames = $this->getSectionUniqNamesByIds(
            $prop['LINK_IBLOCK_ID'],
            $prop['VALUE']
        );

        $tag->addValue($uniqNames);
    }

    /**
     * @throws HelperException
     */
    protected function addPropertyValueElement(WriterTag $tag, $prop): void
    {
        $uniqNames = $this->getElementUniqNamesByIds(
            $prop['LINK_IBLOCK_ID'],
            $prop['VALUE']
        );

        $tag->addValue($uniqNames);
    }

    protected function addPropertyValueList(WriterTag $tag, $prop): void
    {
        $tag->addValue($prop['VALUE_XML_ID']);
    }


    protected function addPropertyValueFile(WriterTag $tag, $prop): void
    {
        $tag->addFile($prop['VALUE']);
    }


    //reader

    /**
     * @throws HelperException
     */
    public function convertRecord(array $attrs, array $record): array
    {
        $iblockId = $this->getIblockIdByUid($attrs['iblockUid']);

        $convertedFields = [];
        foreach ($record['fields'] as $field) {
            if ($field['name'] == 'IBLOCK_SECTION') {
                $convertedFields[$field['name']] = $this->convertFieldIblockSection($iblockId, $field);
            } else {
                $convertedFields[$field['name']] = $this->convertFieldValue($iblockId, $field);
            }
        }

        $convertedProperties = [];
        foreach ($record['properties'] as $prop) {
            $proprtyType = $this->getPropertyType($iblockId, $prop['name']);

            if ($proprtyType == 'L') {
                $convertedProperties[$prop['name']] = $this->convertPropertyL($iblockId, $prop);
            } elseif ($proprtyType == 'F') {
                $convertedProperties[$prop['name']] = $this->convertPropertyF($iblockId, $prop);
            } elseif ($proprtyType == 'G') {
                $convertedProperties[$prop['name']] = $this->convertPropertyG($iblockId, $prop);
            } elseif ($proprtyType == 'E') {
                $convertedProperties[$prop['name']] = $this->convertPropertyE($iblockId, $prop);
            } else {
                $convertedProperties[$prop['name']] = $this->convertPropertyS($iblockId, $prop);
            }
        }

        return [
            'iblock_id' => $iblockId,
            'fields' => $convertedFields,
            'properties' => $convertedProperties,
        ];
    }


    protected function convertFieldValue(int $iblockId, array $field): string
    {
        return $field['value'][0]['value'];
    }

    /**
     * @throws HelperException
     */
    protected function convertFieldIblockSection(int $iblockId, array $field): array
    {
        $value = [];
        foreach ($field['value'] as $val) {
            $value[] = $this->getSectionIdByUniqName($iblockId, $val['value']);
        }

        return $value;
    }

    protected function convertPropertyS(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);
        $res = [];
        foreach ($prop['value'] as $val) {
            $res[] = $this->makePropertyValue($val);
        }

        return ($isMultiple) ? $res : $res[0];
    }

    protected function makePropertyValue(array $val): array
    {
        $result = ['VALUE' => $val['value']];

        if (!empty($val['description'])) {
            $result['DESCRIPTION'] = $val['description'];
        }

        return $result;
    }

    /**
     * @throws HelperException
     */
    protected function convertPropertyG(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);
        $linkIblockId = $this->getPropertyLinkIblockId($iblockId, $prop['name']);

        $res = [];
        if ($linkIblockId) {
            foreach ($prop['value'] as $val) {
                $val['value'] = $this->getSectionIdByUniqName($linkIblockId, $val['value']);
                $res[] = $this->makePropertyValue($val);
            }
        }

        return ($isMultiple) ? $res : $res[0];
    }

    /**
     * @throws HelperException
     */
    protected function convertPropertyE(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);
        $linkIblockId = $this->getPropertyLinkIblockId($iblockId, $prop['name']);

        $res = [];
        if ($linkIblockId) {
            foreach ($prop['value'] as $val) {
                $val['value'] = $this->getElementIdByUniqName($linkIblockId, $val['value']);
                $res[] = $this->makePropertyValue($val);
            }
        }

        return ($isMultiple) ? $res : $res[0];
    }

    protected function convertPropertyF(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);
        $res = [];
        foreach ($prop['value'] as $val) {
            $res[] = $val['value'];
        }
        return ($isMultiple) ? $res : $res[0];
    }

    protected function convertPropertyL(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);
        $res = [];
        foreach ($prop['value'] as $val) {
            $val['value'] = $this->getPropertyEnumIdByXmlId(
                $iblockId,
                $prop['name'],
                $val['value']
            );

            $res[] = $this->makePropertyValue($val);
        }
        return ($isMultiple) ? $res : $res[0];
    }


}
