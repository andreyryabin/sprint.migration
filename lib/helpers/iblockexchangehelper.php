<?php

namespace Sprint\Migration\Helpers;

use _CIBElement;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\WriterTag;
use Sprint\Migration\Interfaces\ReaderHelperInterface;
use Sprint\Migration\Interfaces\WriterHelperInterface;

class IblockExchangeHelper extends IblockHelper implements ReaderHelperInterface, WriterHelperInterface
{
    protected array $cachedProps = [];

    public function getProperty(int $iblockId, string|array $code): array|bool
    {
        $key = $iblockId . $code;

        if (!isset($this->cachedProps[$key])) {
            $this->cachedProps[$key] = parent::getProperty($iblockId, $code);
        }
        return $this->cachedProps[$key];
    }

    /**
     * Структура инфоблоков для построения выпадающего списка
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

    public function getIblockPropertiesStructure(int $iblockId): array
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

    public function getIblockElementFieldsStructure(int $iblockId): array
    {
        $fields = $this->exportIblockElementFields($iblockId);

        $fields['IPROPERTY_TEMPLATES'] = ['NAME' => 'SEO'];

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
    public function getSectionIdByUniqName(int $iblockId, string $uniqName): int
    {
        if (!str_contains($uniqName, '|')) {
            throw new HelperException('Invalid section unique name: ' . $uniqName);
        }


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

        return $this->getSectionIdByUniqFilter($iblockId, $uniqName);
    }

    /**
     * @throws HelperException
     */
    public function getElementUniqNameById(int $iblockId, int $elementId): string
    {
        $filter = $this->getElementUniqFilterById($iblockId, $elementId);
        return $filter['NAME'] . '|' . $filter['XML_ID'] . '|' . $filter['CODE'];
    }

    /**
     * @throws HelperException
     */
    public function getElementIdByUniqName(int $iblockId, string $uniqName): int
    {
        if (!str_contains($uniqName, '|')) {
            throw new HelperException('Invalid element unique name: ' . $uniqName);
        }

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

        return $this->getElementIdByUniqFilter($iblockId, $uniqName);
    }

    /**
     * @throws HelperException
     */
    public function getSectionUniqNameById(int $iblockId, int $sectionId): string
    {
        $filter = $this->getSectionUniqFilterById($iblockId, $sectionId);
        return $filter['NAME'] . '|' . $filter['DEPTH_LEVEL'] . '|' . $filter['CODE'];
    }

    /**
     * @throws HelperException
     */
    public function getSectionUniqNamesByIds(int $iblockId, int|array $sectionIds): array
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
    public function getElementUniqNamesByIds(int $iblockId, int|array $elementIds): array
    {
        $elementIds = array_filter(is_array($elementIds) ? $elementIds : [$elementIds]);

        $uniqNames = [];
        foreach ($elementIds as $elementId) {
            $uniqNames[] = $this->getElementUniqNameById($iblockId, $elementId);
        }
        return $uniqNames;
    }

    /**
     * @throws HelperException
     */
    public function getElementIdsByUniqNames(int $iblockId, array|string $uniqNames): array
    {
        $uniqNames = array_filter(is_array($uniqNames) ? $uniqNames : [$uniqNames]);

        $elementIds = [];
        foreach ($uniqNames as $uniqName) {
            $elementIds[] = $this->getElementIdByUniqName($iblockId, $uniqName);
        }
        return $elementIds;
    }

    public function getElementFields(_CIBElement $element): array
    {
        return $this->prepareElement($element->GetFields());
    }

    public function getElementProps(_CIBElement $element): array
    {
        return $element->GetProperties();
    }

    //writer

    /**
     * @throws HelperException
     */
    public function getWriterAttributes(...$vars): array
    {
        [$iblockId] = $vars;

        return [
            'iblockUid' => $this->getIblockUid($iblockId)
        ];
    }

    public function getWriterRecordsCount(...$vars): int
    {
        [$iblockId, $filter] = $vars;

        return $this->getElementsCount($iblockId, $filter);
    }

    /**
     * @throws HelperException
     */
    public function getWriterRecordsTag(int $offset, int $limit, ...$vars): WriterTag
    {

        [$iblockId, $filter, $exportFields, $exportProps] = $vars;

        $dbres = $this->getElementsList(
            $iblockId,
            [
                'order'  => ['ID' => 'ASC'],
                'offset' => $offset,
                'limit'  => $limit,
                'filter' => $filter,
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
                    $exportProps
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
                        'NAME'      => $code,
                        'VALUE'     => $val,
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
            $tag->addFile($field['VALUE'], false);
        } elseif ($field['NAME'] == 'DETAIL_PICTURE') {
            $tag->addFile($field['VALUE'], false);
        } elseif ($field['NAME'] == 'IBLOCK_SECTION') {
            if (!empty($field['VALUE'])) {
                $tag->addValue(
                    $this->getSectionUniqNamesByIds($field['IBLOCK_ID'], $field['VALUE']),
                    true
                );
            }
        } elseif ($field['NAME'] == 'IPROPERTY_TEMPLATES') {
            foreach ($field['VALUE'] as $ikey => $ivalue) {
                $tag->addValueTag($ivalue, ['name' => $ikey]);
            }
        } else {
            $tag->addValue($field['VALUE'], false);
        }
        return $tag;
    }

    /**
     * @throws HelperException
     */
    protected function createPropertyTag(array $prop): WriterTag
    {
        $tag = new WriterTag('property', ['name' => $prop['CODE']]);

        $propType = $prop['PROPERTY_TYPE'];
        $userType = $prop['USER_TYPE'];

        if ($propType == 'F') {
            $this->addPropertyValueFile($tag, $prop);
        } elseif ($propType == 'L') {
            $this->addPropertyValueList($tag, $prop);
        } elseif ($propType == 'G') {
            $this->addPropertyValueSection($tag, $prop);
        } elseif ($propType == 'E') {
            $this->addPropertyValueElement($tag, $prop);
        } elseif ($propType == 'S' && $userType == 'HTML') {
            $this->addPropertyValueHtml($tag, $prop);
        } else {
            $this->addPropertyValueString($tag, $prop);
        }
        return $tag;
    }

    protected function addPropertyValueString(WriterTag $tag, $prop): void
    {
        if ($prop['MULTIPLE'] == 'Y') {
            foreach ($prop['VALUE'] as $index => $val1) {
                $tag->addValueTag($val1, ['description' => $prop['DESCRIPTION'][$index] ?? '']);
            }
        } else {
            $tag->addValueTag($prop['VALUE'], ['description' => $prop['DESCRIPTION']]);
        }
    }

    protected function addPropertyValueHtml(WriterTag $tag, $prop): void
    {
        if ($prop['MULTIPLE'] == 'Y') {
            foreach ($prop['VALUE'] as $index => $val1) {
                $tag->addValueTag(
                    $val1['TEXT'],
                    [
                        'text-type'   => $val1['TYPE'],
                        'description' => $prop['DESCRIPTION'][$index] ?? ''
                    ]
                );
            }
        } else {
            $tag->addValueTag(
                $prop['VALUE']['TEXT'],
                [
                    'text-type'   => $prop['VALUE']['TYPE'],
                    'description' => $prop['DESCRIPTION']
                ]
            );
        }
    }

    /**
     * @throws HelperException
     */
    protected function addPropertyValueSection(WriterTag $tag, $prop): void
    {
        if (!empty($prop['VALUE'])) {
            $tag->addValue(
                $this->getSectionUniqNamesByIds($prop['LINK_IBLOCK_ID'], $prop['VALUE']),
                true
            );
        }
    }

    /**
     * @throws HelperException
     */
    protected function addPropertyValueElement(WriterTag $tag, $prop): void
    {
        if (!empty($prop['VALUE'])) {
            $tag->addValue(
                $this->getElementUniqNamesByIds($prop['LINK_IBLOCK_ID'], $prop['VALUE']),
                true
            );
        }
    }

    protected function addPropertyValueList(WriterTag $tag, $prop): void
    {
        $tag->addValue($prop['VALUE_XML_ID'], $prop['MULTIPLE'] == 'Y');
    }


    protected function addPropertyValueFile(WriterTag $tag, $prop): void
    {
        $tag->addFile($prop['VALUE'], $prop['MULTIPLE'] == 'Y');
    }


    //reader

    /**
     * @throws HelperException
     */
    public function convertReaderRecords(array $attributes, array $records): array
    {
        $iblockId = $this->getIblockIdByUid($attributes['iblockUid']);

        return array_map(fn($record) => $this->convertReaderRecord($iblockId, $record), $records);
    }

    /**
     * @throws HelperException
     */
    protected function convertReaderRecord(int $iblockId, array $record): array
    {
        $convertedFields = [];
        foreach ($record['fields'] as $field) {
            if ($field['name'] == 'IBLOCK_SECTION') {
                $convertedFields[$field['name']] = $this->convertFieldIblockSection($iblockId, $field);
            } elseif ($field['name'] == 'IPROPERTY_TEMPLATES') {
                $convertedFields[$field['name']] = $this->convertFieldIpropertyTemplates($field);
            } else {
                $convertedFields[$field['name']] = $this->convertFieldValue($field);
            }
        }

        $convertedProperties = [];
        foreach ($record['properties'] as $prop) {
            $propType = $this->getPropertyType($iblockId, $prop['name']);
            $userType = $this->getPropertyUserType($iblockId, $prop['name']);

            if ($propType == 'L') {
                $convertedProperties[$prop['name']] = $this->convertPropertyL($iblockId, $prop);
            } elseif ($propType == 'F') {
                $convertedProperties[$prop['name']] = $this->convertPropertyF($iblockId, $prop);
            } elseif ($propType == 'G') {
                $convertedProperties[$prop['name']] = $this->convertPropertyG($iblockId, $prop);
            } elseif ($propType == 'E') {
                $convertedProperties[$prop['name']] = $this->convertPropertyE($iblockId, $prop);
            } elseif ($propType == 'S' && $userType == 'HTML') {
                $convertedProperties[$prop['name']] = $this->convertPropertyHtml($iblockId, $prop);
            } else {
                $convertedProperties[$prop['name']] = $this->convertPropertyS($iblockId, $prop);
            }
        }

        return [
            'iblock_id'  => $iblockId,
            'fields'     => $convertedFields,
            'properties' => $convertedProperties,
        ];
    }


    protected function convertFieldValue(array $field)
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

    protected function convertFieldIpropertyTemplates(array $field): array
    {
        $iproperty = [];
        foreach ($field['value'] as $val) {
            $iproperty[$val['name']] = $val['value'];
        }
        return $iproperty;
    }

    protected function convertPropertyHtml(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);
        $res = [];
        foreach ($prop['value'] as $val) {
            $res[] = [
                'VALUE'       => [
                    'TEXT' => $val['value'],
                    'TYPE' => $val['text-type'] ?? 'html',
                ],
                'DESCRIPTION' => $val['description'] ?? ''
            ];
        }

        return ($isMultiple) ? $res : $res[0];
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
