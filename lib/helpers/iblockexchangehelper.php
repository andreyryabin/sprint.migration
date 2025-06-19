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
            'iblockUid' => $this->getIblockUid($iblockId),
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
                )
            );
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
    ): WriterTag {
        $item = new WriterTag('item');

        foreach ($fields as $code => $val) {
            if (in_array($code, $exportFields)) {
                $item->addChild(
                    $this->createFieldTag([
                        'NAME'      => $code,
                        'VALUE'     => $val,
                        'IBLOCK_ID' => $iblockId,
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
            $this->addFieldValueSection($tag, $field);
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
    private function addFieldValueSection(WriterTag $tag, array $field): void
    {
        if (!empty($field['VALUE']) && !empty($field['IBLOCK_ID'])) {
            foreach ($this->makeNonEmptyArray($field['VALUE']) as $sectionId) {
                $this->addValueTagIblockSection($tag, $field['IBLOCK_ID'], $sectionId);
            }
        }
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
                        'description' => $prop['DESCRIPTION'][$index] ?? '',
                    ]
                );
            }
        } else {
            $tag->addValueTag(
                $prop['VALUE']['TEXT'] ?? '',
                [
                    'text-type'   => $prop['VALUE']['TYPE'] ?? '',
                    'description' => $prop['DESCRIPTION'],
                ]
            );
        }
    }

    /**
     * @throws HelperException
     */
    protected function addPropertyValueSection(WriterTag $tag, $prop): void
    {
        if (!empty($prop['VALUE']) && !empty($prop['LINK_IBLOCK_ID'])) {
            foreach ($this->makeNonEmptyArray($prop['VALUE']) as $sectionId) {
                $this->addValueTagIblockSection($tag, $prop['LINK_IBLOCK_ID'], $sectionId);
            }
        }
    }

    /**
     * @throws HelperException
     */
    protected function addValueTagIblockSection(WriterTag $tag, int $iblockId, int $sectionId): void
    {
        if (!empty($iblockId) && !empty($sectionId)) {
            $item = $this->getSectionIfExists($iblockId, ['ID' => $sectionId]);

            $tag->addValueTag(
                $item['NAME'],
                [
                    'section_depth_level' => $item['DEPTH_LEVEL'],
                    'section_code'        => $item['CODE'],
                ]
            );
        }
    }

    /**
     * @throws HelperException
     */
    protected function addPropertyValueElement(WriterTag $tag, $prop): void
    {
        if (!empty($prop['VALUE']) && !empty($prop['LINK_IBLOCK_ID'])) {
            foreach ($this->makeNonEmptyArray($prop['VALUE']) as $elementId) {
                $item = $this->getElementIfExists(
                    $prop['LINK_IBLOCK_ID'],
                    ['ID' => $elementId]
                );

                $tag->addValueTag(
                    $item['NAME'],
                    [
                        'element_xml_id' => $item['XML_ID'],
                        'element_code'   => $item['CODE'],
                    ]
                );
            }
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
                $convertedFields[$field['name']] = $this->convertFieldSection($iblockId, $field);
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
                $convertedProperties[$prop['name']] = $this->convertPropertyList($iblockId, $prop);
            } elseif ($propType == 'F') {
                $convertedProperties[$prop['name']] = $this->convertPropertyFile($iblockId, $prop);
            } elseif ($propType == 'G') {
                $convertedProperties[$prop['name']] = $this->convertPropertySection($iblockId, $prop);
            } elseif ($propType == 'E') {
                $convertedProperties[$prop['name']] = $this->convertPropertyElement($iblockId, $prop);
            } elseif ($propType == 'S' && $userType == 'HTML') {
                $convertedProperties[$prop['name']] = $this->convertPropertyHtml($iblockId, $prop);
            } else {
                $convertedProperties[$prop['name']] = $this->convertPropertyString($iblockId, $prop);
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
    protected function convertFieldSection(int $iblockId, array $field): array
    {
        return array_map(
            fn($val) => $this->convertValueIblockSection($iblockId, $val),
            $field['value']
        );
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
                'DESCRIPTION' => $val['description'] ?? '',
            ];
        }

        return ($isMultiple) ? $res : $res[0];
    }

    protected function convertPropertyString(int $iblockId, array $prop)
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
    protected function makePropertyValueElement(int $iblockId, array $val): array
    {
        $this->checkRequiredKeys($val, ['element_xml_id', 'element_code', 'value']);

        $elementId = $this->getElementIdIfExists($iblockId, [
            'NAME'   => $val['value'],
            'CODE'   => $val['element_code'],
            'XML_ID' => $val['element_xml_id'],
        ]);

        return ['VALUE' => $elementId];
    }

    /**
     * @throws HelperException
     */
    protected function convertValueIblockSection(int $iblockId, array $val): int
    {
        $this->checkRequiredKeys($val, ['section_depth_level', 'section_code', 'value']);

        return $this->getSectionIdIfExists($iblockId, [
            'NAME'        => $val['value'],
            'CODE'        => $val['section_code'],
            'DEPTH_LEVEL' => $val['section_depth_level'],
        ]);
    }

    /**
     * @throws HelperException
     */
    protected function convertPropertySection(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);
        $linkIblockId = $this->getPropertyLinkIblockId($iblockId, $prop['name']);

        if ($linkIblockId) {
            $res = array_map(
                fn($val) => ['VALUE' => $this->convertValueIblockSection($iblockId, $val)],
                $prop['value']
            );
            return ($isMultiple) ? $res : ($res[0] ?? false);
        }

        return ($isMultiple) ? [] : false;
    }

    /**
     * @throws HelperException
     */
    protected function convertPropertyElement(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);
        $linkIblockId = $this->getPropertyLinkIblockId($iblockId, $prop['name']);

        $res = $linkIblockId ? array_map(
            fn($val) => $this->makePropertyValueElement($linkIblockId, $val),
            $prop['value']
        ) : [];

        return ($isMultiple) ? $res : ($res[0] ?? false);
    }

    protected function convertPropertyFile(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);

        $res = array_map(fn($val) => $val['value'], $prop['value']);

        return ($isMultiple) ? $res : $res[0];
    }

    protected function convertPropertyList(int $iblockId, array $prop)
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
