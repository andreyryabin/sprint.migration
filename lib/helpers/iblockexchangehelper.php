<?php

namespace Sprint\Migration\Helpers;

use _CIBElement;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\FileExchange;
use Sprint\Migration\Exchange\WriterTag;
use Sprint\Migration\Interfaces\ReaderHelperInterface;
use Sprint\Migration\Interfaces\WriterHelperInterface;
use Sprint\Migration\Module;

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

    /**
     * @throws HelperException
     */
    public function getIblockPropertiesStructure(int $iblockId): array
    {
        return array_map(fn($prop) => [
            'title' => '[' . $prop['CODE'] . '] ' . $prop['NAME'],
            'value' => $prop['CODE'],
        ], $this->exportProperties($iblockId));
    }

    public function getIblockElementFieldsStructure(int $iblockId, bool $withShowCounter = false): array
    {
        $fields = $this->exportIblockElementFields($iblockId);

        $fields['IPROPERTY_TEMPLATES'] = ['NAME' => 'SEO'];

        if ($withShowCounter) {
            $fields['SHOW_COUNTER'] = ['NAME' => 'SHOW_COUNTER'];
            $fields['SHOW_COUNTER_START'] = ['NAME' => 'SHOW_COUNTER_START'];
        }

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
        [$iblockId, $filter, $exportFields, $exportProps, $exchangeDir] = array_pad($vars, 5, '');

        $select = array_values(array_unique(array_merge(
            ['ID', 'IBLOCK_ID'],
            array_diff($exportFields, ['IBLOCK_SECTION', 'IPROPERTY_TEMPLATES'])
        )));

        $dbres = $this->getElementsList(
            $iblockId,
            [
                'order'  => ['ID' => 'ASC'],
                'offset' => $offset,
                'limit'  => $limit,
                'filter' => $filter,
                'select' => $select,
            ]
        );

        $tag = new WriterTag('tmp');
        while ($element = $dbres->GetNextElement(false, false)) {
            $tag->addChild(
                $this->createWriterRecordTag(
                    $iblockId,
                    $this->getElementFields($element),
                    $this->getElementProps($element),
                    $exportFields,
                    $exportProps,
                    (string)$exchangeDir
                )
            );
        }
        return $tag;
    }

    /**
     * @throws HelperException
     */
    public function writeValueIblockSection(WriterTag $tag, int $iblockId, int $sectionId): void
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
    public function writeValueIblockElement(WriterTag $tag, int $iblockId, int $elementId): void
    {
        if (!empty($iblockId) && !empty($elementId)) {
            $item = $this->getElementIfExists($iblockId, ['ID' => $elementId]);

            $tag->addValueTag(
                $item['NAME'],
                [
                    'element_xml_id' => $item['XML_ID'],
                    'element_code'   => $item['CODE'],
                ]
            );
        }
    }

    /**
     * @throws HelperException
     */
    protected function createWriterRecordTag(
        $iblockId,
        array $fields,
        array $props,
        array $exportFields,
        array $exportProperties,
        string $exchangeDir = ''
    ): WriterTag {
        $item = new WriterTag('item');

        foreach ($exportFields as $code) {
            if (!array_key_exists($code, $fields) && !array_key_exists('~' . $code, $fields)) {
                continue;
            }

            $val = $this->getWriterFieldValueForExport($code, $fields);
            $item->addChild(
                $this->createWriterFieldTag([
                    'NAME'      => $code,
                    'VALUE'     => $val,
                    'IBLOCK_ID' => $iblockId,
                ])
            );

            if (
                Module::EXCHANGE_VERSION > Module::EXCHANGE_VERSION_LEGACY
                && $exchangeDir !== ''
                && in_array($code, ['PREVIEW_TEXT', 'DETAIL_TEXT'])
            ) {
                $this->addTextFileLinksTag(
                    $item,
                    'field',
                    $code,
                    (string)$val,
                    $exchangeDir
                );
            }
        }

        foreach ($props as $prop) {
            if (in_array($prop['CODE'], $exportProperties)) {
                $item->addChild(
                    $this->createWriterPropertyTag($prop)
                );

                if (
                    Module::EXCHANGE_VERSION > Module::EXCHANGE_VERSION_LEGACY
                    && $exchangeDir !== ''
                    && $prop['PROPERTY_TYPE'] == 'S'
                    && $prop['USER_TYPE'] == 'HTML'
                ) {
                    $this->addPropertyHtmlFileLinksTags($item, $prop, $exchangeDir);
                }
            }
        }

        return $item;
    }

    protected function getWriterFieldValueForExport(string $code, array $fields): mixed
    {
        $value = $fields['~' . $code] ?? $fields[$code] ?? null;

        if (in_array($code, ['PREVIEW_TEXT', 'DETAIL_TEXT'])) {
            $type = (string)($fields[$code . '_TYPE'] ?? $fields['~' . $code . '_TYPE'] ?? '');
            if (strtolower($type) == 'html' && !array_key_exists('~' . $code, $fields)) {
                return $this->decodeHtmlValue((string)$value);
            }
        }

        return $value;
    }

    protected function decodeHtmlValue(string $value): string
    {
        if (function_exists('htmlspecialcharsback')) {
            return htmlspecialcharsback($value);
        }

        return html_entity_decode($value, ENT_QUOTES | ENT_HTML5);
    }

    protected function addTextFileLinksTag(
        WriterTag $item,
        string $scope,
        string $name,
        string $text,
        string $exchangeDir,
        int $index = 0
    ): void {
        $fileLinks = (new FileExchange())->exportTextFileLinks(
            $text,
            $exchangeDir,
            'iblock_element_text'
        );

        if (empty($fileLinks)) {
            return;
        }

        $tag = new WriterTag('file_links', [
            'scope' => $scope,
            'name'  => $name,
            'index' => $index,
        ]);

        foreach ($fileLinks as $source => $fileRef) {
            $tag->addFileRefTag($fileRef, ['source' => $source]);
        }

        $item->addChild($tag);
    }

    protected function addPropertyHtmlFileLinksTags(WriterTag $item, array $prop, string $exchangeDir): void
    {
        if ($prop['MULTIPLE'] == 'Y') {
            foreach ($this->getPropertyHtmlValues($prop) as $index => $value) {
                $htmlValue = $this->normalizeHtmlPropertyValue($value);
                $this->addTextFileLinksTag(
                    $item,
                    'property',
                    $prop['CODE'],
                    $htmlValue['TEXT'],
                    $exchangeDir,
                    $index
                );
            }
            return;
        }

        $htmlValue = $this->normalizeHtmlPropertyValue($prop['VALUE']);
        $this->addTextFileLinksTag(
            $item,
            'property',
            $prop['CODE'],
            $htmlValue['TEXT'],
            $exchangeDir
        );
    }

    protected function normalizeHtmlPropertyValue(mixed $value): array
    {
        if (is_array($value)) {
            return [
                'TEXT' => $this->decodeHtmlValue((string)($value['TEXT'] ?? '')),
                'TYPE' => (string)($value['TYPE'] ?? 'html'),
            ];
        }

        return [
            'TEXT' => $this->decodeHtmlValue((string)$value),
            'TYPE' => 'html',
        ];
    }

    protected function getPropertyHtmlValues(array $prop): array
    {
        return array_values(is_array($prop['VALUE']) ? $prop['VALUE'] : [$prop['VALUE']]);
    }

    /**
     * @throws HelperException
     */
    protected function createWriterFieldTag(array $field): WriterTag
    {
        $tag = new WriterTag('field', ['name' => $field['NAME']]);

        if ($field['NAME'] == 'PREVIEW_PICTURE') {
            $tag->addFile($field['VALUE'], false);
        } elseif ($field['NAME'] == 'DETAIL_PICTURE') {
            $tag->addFile($field['VALUE'], false);
        } elseif ($field['NAME'] == 'IBLOCK_SECTION') {
            $this->writeFieldValueSection($tag, $field);
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
    protected function writeFieldValueSection(WriterTag $tag, array $field): void
    {
        if (!empty($field['VALUE']) && !empty($field['IBLOCK_ID'])) {
            foreach ($this->makeNonEmptyArray($field['VALUE']) as $sectionId) {
                $this->writeValueIblockSection($tag, $field['IBLOCK_ID'], $sectionId);
            }
        }
    }

    /**
     * @throws HelperException
     */
    protected function createWriterPropertyTag(array $prop): WriterTag
    {
        $tag = new WriterTag('property', ['name' => $prop['CODE']]);

        $propType = $prop['PROPERTY_TYPE'];
        $userType = $prop['USER_TYPE'];

        if ($propType == 'F') {
            $this->writePropertyFile($tag, $prop);
        } elseif ($propType == 'L') {
            $this->writePropertyList($tag, $prop);
        } elseif ($propType == 'G') {
            $this->writePropertySection($tag, $prop);
        } elseif ($propType == 'E') {
            $this->writePropertyElement($tag, $prop);
        } elseif ($propType == 'S' && $userType == 'HTML') {
            $this->writePropertyHtml($tag, $prop);
        } else {
            $this->writePropertyString($tag, $prop);
        }
        return $tag;
    }

    protected function writePropertyString(WriterTag $tag, $prop): void
    {
        if ($prop['MULTIPLE'] == 'Y') {
            foreach ($prop['VALUE'] as $index => $val1) {
                $tag->addValueTag($val1, ['description' => $prop['DESCRIPTION'][$index] ?? '']);
            }
        } else {
            $tag->addValueTag($prop['VALUE'], ['description' => $prop['DESCRIPTION']]);
        }
    }

    protected function writePropertyHtml(WriterTag $tag, $prop): void
    {
        if ($prop['MULTIPLE'] == 'Y') {
            foreach ($this->getPropertyHtmlValues($prop) as $index => $val1) {
                $htmlValue = $this->normalizeHtmlPropertyValue($val1);
                $tag->addValueTag(
                    $htmlValue['TEXT'],
                    [
                        'text-type'   => $htmlValue['TYPE'],
                        'description' => $prop['DESCRIPTION'][$index] ?? '',
                    ]
                );
            }
        } else {
            $htmlValue = $this->normalizeHtmlPropertyValue($prop['VALUE']);
            $tag->addValueTag(
                $htmlValue['TEXT'],
                [
                    'text-type'   => $htmlValue['TYPE'],
                    'description' => $prop['DESCRIPTION'],
                ]
            );
        }
    }

    /**
     * @throws HelperException
     */
    protected function writePropertySection(WriterTag $tag, $prop): void
    {
        if (!empty($prop['VALUE']) && !empty($prop['LINK_IBLOCK_ID'])) {
            foreach ($this->makeNonEmptyArray($prop['VALUE']) as $sectionId) {
                $this->writeValueIblockSection($tag, $prop['LINK_IBLOCK_ID'], $sectionId);
            }
        }
    }

    /**
     * @throws HelperException
     */
    protected function writePropertyElement(WriterTag $tag, $prop): void
    {
        if (!empty($prop['VALUE']) && !empty($prop['LINK_IBLOCK_ID'])) {
            foreach ($this->makeNonEmptyArray($prop['VALUE']) as $elementId) {
                $this->writeValueIblockElement($tag, $prop['LINK_IBLOCK_ID'], $elementId);
            }
        }
    }

    protected function writePropertyList(WriterTag $tag, $prop): void
    {
        $tag->addValue($prop['VALUE_XML_ID'], $prop['MULTIPLE'] == 'Y');
    }

    protected function writePropertyFile(WriterTag $tag, $prop): void
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
    public function readValueIblockElement(int $iblockId, array $val): int
    {
        $this->checkRequiredKeys($val, ['element_xml_id', 'element_code', 'value']);

        return $this->getElementIdIfExists($iblockId, array_filter([
            'NAME'   => $val['value'],
            'CODE'   => $val['element_code'] ?? '',
            'XML_ID' => $val['element_xml_id'],
        ]));
    }

    /**
     * @throws HelperException
     */
    public function readValueIblockSection(int $iblockId, array $val): int
    {
        $this->checkRequiredKeys($val, ['section_depth_level', 'value']);

        return $this->getSectionIdIfExists($iblockId, array_filter([
            'NAME'        => $val['value'],
            'CODE'        => $val['section_code'] ?? '',
            'DEPTH_LEVEL' => $val['section_depth_level'],
        ]));
    }

    /**
     * @throws HelperException
     */
    protected function convertReaderRecord(int $iblockId, array $record): array
    {
        $fileLinks = $this->groupReaderFileLinks($record['file_links'] ?? []);

        $convertedFields = [];
        foreach ($record['fields'] as $field) {
            if ($field['name'] == 'IBLOCK_SECTION') {
                $convertedFields[$field['name']] = $this->readFieldSection($iblockId, $field);
            } elseif ($field['name'] == 'IPROPERTY_TEMPLATES') {
                $convertedFields[$field['name']] = $this->readFieldIpropertyTemplates($field);
            } elseif (in_array($field['name'], ['PREVIEW_TEXT', 'DETAIL_TEXT'])) {
                $convertedFields[$field['name']] = $this->importXmlTextFileLinks(
                    $this->readFieldValue($field),
                    $fileLinks['field'][$field['name']][0] ?? []
                );
            } else {
                $convertedFields[$field['name']] = $this->readFieldValue($field);
            }
        }

        $convertedProperties = [];
        foreach ($record['properties'] as $prop) {
            $propType = $this->getPropertyType($iblockId, $prop['name']);
            $userType = $this->getPropertyUserType($iblockId, $prop['name']);

            if ($propType == 'L') {
                $convertedProperties[$prop['name']] = $this->readPropertyList($iblockId, $prop);
            } elseif ($propType == 'F') {
                $convertedProperties[$prop['name']] = $this->readPropertyFile($iblockId, $prop);
            } elseif ($propType == 'G') {
                $convertedProperties[$prop['name']] = $this->readPropertySection($iblockId, $prop);
            } elseif ($propType == 'E') {
                $convertedProperties[$prop['name']] = $this->readPropertyElement($iblockId, $prop);
            } elseif ($propType == 'S' && $userType == 'HTML') {
                $convertedProperties[$prop['name']] = $this->readPropertyHtml(
                    $iblockId,
                    $prop,
                    $fileLinks['property'][$prop['name']] ?? []
                );
            } else {
                $convertedProperties[$prop['name']] = $this->readPropertyString($iblockId, $prop);
            }
        }

        return [
            'iblock_id'  => $iblockId,
            'fields'     => $convertedFields,
            'properties' => $convertedProperties,
        ];
    }

    protected function groupReaderFileLinks(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $scope = (string)($item['scope'] ?? '');
            $name = (string)($item['name'] ?? '');
            $index = (int)($item['index'] ?? 0);

            if ($scope === '' || $name === '') {
                continue;
            }

            foreach (($item['value'] ?? []) as $value) {
                $source = (string)($value['source'] ?? '');
                if ($source === '' || empty($value['value']) || !is_array($value['value'])) {
                    continue;
                }

                $result[$scope][$name][$index][$source] = $value['value'];
            }
        }

        return $result;
    }

    protected function readFieldValue(array $field)
    {
        return $field['value'][0]['value'];
    }

    /**
     * @throws HelperException
     */
    protected function readFieldSection(int $iblockId, array $field): array
    {
        return array_map(
            fn($val) => $this->readValueIblockSection($iblockId, $val),
            $field['value']
        );
    }

    protected function readFieldIpropertyTemplates(array $field): array
    {
        $iproperty = [];
        foreach ($field['value'] as $val) {
            $iproperty[$val['name']] = $val['value'];
        }
        return $iproperty;
    }

    protected function readPropertyHtml(int $iblockId, array $prop, array $fileLinks = [])
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);
        $res = [];
        foreach ($prop['value'] as $index => $val) {
            $res[] = [
                'VALUE'       => [
                    'TEXT' => $this->importXmlTextFileLinks(
                        $val['value'],
                        $fileLinks[$index] ?? []
                    ),
                    'TYPE' => $val['text-type'] ?? 'html',
                ],
                'DESCRIPTION' => $val['description'] ?? '',
            ];
        }

        return ($isMultiple) ? $res : $res[0];
    }

    protected function importXmlTextFileLinks(string $text, array $fileLinks): string
    {
        if (empty($fileLinks)) {
            return $text;
        }

        $linkMap = [];
        foreach ($fileLinks as $oldLink => $file) {
            if (!is_array($file)) {
                continue;
            }

            Module::prepareLongDatabaseConnection();
            $fileId = \CFile::SaveFile($file, 'sprint_migration/iblock_element_text');
            Module::reconnectDatabase();

            if ($fileId) {
                $linkMap[$oldLink] = (string)\CFile::GetPath($fileId);
            }
        }

        return (new FileExchange())->replaceTextFileLinks($text, $linkMap);
    }

    protected function readPropertyString(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);

        $res = array_map(fn($val) => $this->readPropertyValue($val), $prop['value']);

        return ($isMultiple) ? $res : $res[0];
    }

    protected function readPropertyValue(array $val): array
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
    protected function readPropertySection(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);
        $linkIblockId = $this->getPropertyLinkIblockId($iblockId, $prop['name']);

        if ($linkIblockId) {
            $res = array_map(
                fn($val) => ['VALUE' => $this->readValueIblockSection($linkIblockId, $val)],
                $prop['value']
            );
            return ($isMultiple) ? $res : ($res[0] ?? false);
        }

        return ($isMultiple) ? [] : false;
    }

    /**
     * @throws HelperException
     */
    protected function readPropertyElement(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);
        $linkIblockId = $this->getPropertyLinkIblockId($iblockId, $prop['name']);

        if ($linkIblockId) {
            $res = array_map(
                fn($val) => ['VALUE' => $this->readValueIblockElement($linkIblockId, $val)],
                $prop['value']
            );
            return ($isMultiple) ? $res : ($res[0] ?? false);
        }
        return ($isMultiple) ? [] : false;
    }

    protected function readPropertyFile(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);

        $res = array_map(fn($val) => $val['value'], $prop['value']);

        return ($isMultiple) ? $res : $res[0];
    }

    protected function readPropertyList(int $iblockId, array $prop)
    {
        $isMultiple = $this->isPropertyMultiple($iblockId, $prop['name']);
        $res = [];
        foreach ($prop['value'] as $val) {
            $val['value'] = $this->getPropertyEnumIdByXmlId(
                $iblockId,
                $prop['name'],
                $val['value']
            );

            $res[] = $this->readPropertyValue($val);
        }
        return ($isMultiple) ? $res : $res[0];
    }

}
