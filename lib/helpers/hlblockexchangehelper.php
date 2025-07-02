<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\WriterTag;
use Sprint\Migration\Interfaces\ReaderHelperInterface;
use Sprint\Migration\Interfaces\WriterHelperInterface;
use Sprint\Migration\Locale;

class HlblockExchangeHelper extends HlblockHelper implements ReaderHelperInterface, WriterHelperInterface
{
    protected array $cachedFields = [];

    /**
     * @throws HelperException
     */
    public function getField($hlblockName, $fieldName): array
    {
        $key = $hlblockName . $fieldName;

        if (!isset($this->cachedFields[$key])) {
            $this->cachedFields[$key] = parent::getField($hlblockName, $fieldName);
        }
        return $this->cachedFields[$key];
    }

    /**
     * @throws HelperException
     */
    public function getHlblocksStructure(): array
    {
        return array_map(
            fn($hlblock) => [
                'title' => Locale::getMessage('HLBLOCK_TITLE', $hlblock),
                'value' => (int)$hlblock['ID'],
            ],
            $this->getHlblocks()
        );
    }

    /**
     * @throws HelperException
     */
    public function getHlblockFieldsStructure(int $hlblockId): array
    {
        return array_map(
            fn($field) => [
                'title' => Locale::getMessage('HLBLOCK_FIELD', $field),
                'value' => $field['FIELD_NAME'],
            ],
            $this->getFields($hlblockId)
        );
    }


    //reader

    /**
     * @throws HelperException
     */
    public function convertReaderRecords(array $attributes, array $records): array
    {
        $hlblockId = $this->getHlblockIdIfExists($attributes['hlblockUid']);

        return array_map(fn($record) => $this->convertReaderRecord($hlblockId, $record), $records);
    }

    /**
     * @throws HelperException
     */
    protected function convertReaderRecord(int $hlblockId, array $record): array
    {
        $convertedFields = [];
        foreach ($record['fields'] as $field) {
            $fieldType = $this->getFieldType($hlblockId, $field['name']);

            if ($fieldType == 'enumeration') {
                $convertedFields[$field['name']] = $this->readFieldEnumeration($hlblockId, $field);
            } elseif ($fieldType == 'iblock_element') {
                $convertedFields[$field['name']] = $this->readFieldIblockElement($hlblockId, $field);
            } elseif ($fieldType == 'iblock_section') {
                $convertedFields[$field['name']] = $this->readFieldIblockSection($hlblockId, $field);
            } elseif ($fieldType == 'hlblock') {
                $convertedFields[$field['name']] = $this->readFieldHlblockElement($hlblockId, $field);
            } else {
                $convertedFields[$field['name']] = $this->readFieldValue($hlblockId, $field);
            }
        }
        return [
            'hlblock_id' => $hlblockId,
            'fields'     => $convertedFields,
        ];
    }

    /**
     * @throws HelperException
     */
    protected function readFieldValue(int $hlblockId, array $field)
    {
        if ($this->isFieldMultiple($hlblockId, $field['name'])) {
            $res = [];
            foreach ($field['value'] as $val) {
                $res[] = $val['value'];
            }
            return $res;
        } else {
            return $field['value'][0]['value'];
        }
    }

    /**
     * @throws HelperException
     */
    protected function readFieldEnumeration(int $hlblockId, array $field)
    {
        if ($this->isFieldMultiple($hlblockId, $field['name'])) {
            return array_map(fn($val) => $this->getFieldEnumIdByXmlId(
                $hlblockId,
                $field['name'],
                $val['value']
            ), $field['value']);
        } else {
            return $this->getFieldEnumIdByXmlId(
                $hlblockId,
                $field['name'],
                $field['value'][0]['value']
            );
        }
    }

    /**
     * @throws HelperException
     */
    protected function readFieldIblockElement(int $hlblockId, array $field)
    {
        $isMultiple = $this->isFieldMultiple($hlblockId, $field['name']);
        $settings = $this->getFieldSettings($hlblockId, $field['name']);

        $linkIblockId = $settings['IBLOCK_ID'] ?? '';

        if ($linkIblockId) {
            $res = array_map(
                fn($val) => (new IblockExchangeHelper())->readValueIblockElement($linkIblockId, $val),
                $field['value']
            );
            return ($isMultiple) ? $res : ($res[0] ?? false);
        }
        return ($isMultiple) ? [] : false;
    }

    /**
     * @throws HelperException
     */
    protected function readFieldIblockSection(int $hlblockId, array $field)
    {
        $isMultiple = $this->isFieldMultiple($hlblockId, $field['name']);
        $settings = $this->getFieldSettings($hlblockId, $field['name']);

        $linkIblockId = $settings['IBLOCK_ID'] ?? '';

        if ($linkIblockId) {
            $res = array_map(
                fn($val) => (new IblockExchangeHelper())->readValueIblockSection($linkIblockId, $val),
                $field['value']
            );
            return ($isMultiple) ? $res : ($res[0] ?? false);
        }

        return ($isMultiple) ? [] : false;
    }

    /**
     * @throws HelperException
     */
    protected function readFieldHlblockElement(int $hlblockId, array $field)
    {
        $isMultiple = $this->isFieldMultiple($hlblockId, $field['name']);
        $settings = $this->getFieldSettings($hlblockId, $field['name']);

        $linkHlblockId = $settings['HLBLOCK_ID'] ?? '';

        if ($linkHlblockId) {
            $res = array_map(
                fn($val) => $this->readValueHlblockElement($linkHlblockId, $val),
                $field['value']
            );
            return ($isMultiple) ? $res : ($res[0] ?? false);
        }

        return ($isMultiple) ? [] : false;
    }

    /**
     * @throws HelperException
     */
    public function readValueHlblockElement(int $hlblockId, array $val): int
    {
        $this->checkRequiredKeys($val, ['value']);

        //если есть xml_id ищем элемент по нему

        $filter = array_filter([
            'UF_XML_ID' => $val['uf_xml_id'] ?? '',
        ]);

        if (!empty($filter)) {
            return $this->getElementIdIfExists($hlblockId, $filter);
        }

        //иначе сохраняем значение как есть
        // да, тут может быть несуществующий или некорректный id связанного элемента

        return (int)$val['value'];
    }
    //writer

    /**
     * @throws HelperException
     */
    public function getWriterAttributes(...$vars): array
    {
        [$hlblockId] = $vars;

        return [
            'hlblockUid' => $this->getHlblockNameById($hlblockId),
        ];
    }

    /**
     * @throws HelperException
     */
    public function getWriterRecordsCount(...$vars): int
    {
        [$hlblockId, $filter] = $vars;

        return $this->getElementsCount($hlblockId, $filter);
    }

    /**
     * @throws HelperException
     */
    public function getWriterRecordsTag(int $offset, int $limit, ...$vars): WriterTag
    {
        [$hlblockId, $filter, $exportFields] = $vars;

        $elements = $this->getElements(
            $hlblockId,
            [
                'order'  => ['ID' => 'ASC'],
                'offset' => $offset,
                'limit'  => $limit,
                'filter' => $filter,
            ]
        );

        $tag = new WriterTag('tmp');
        foreach ($elements as $element) {
            $tag->addChild(
                $this->createWriterRecordTag(
                    $hlblockId,
                    $element,
                    $exportFields
                )
            );
        }

        return $tag;
    }

    /**
     * @throws HelperException
     */
    protected function createWriterRecordTag($hlblockId, array $element, array $exportFields): WriterTag
    {
        $item = new WriterTag('item');

        foreach ($element as $code => $val) {
            if (in_array($code, $exportFields)) {
                $item->addChild(
                    $this->createWriterFieldTag(
                        array_merge(
                            $this->getField($hlblockId, $code),
                            [
                                'HLBLOCK_ID' => $hlblockId,
                                'VALUE'      => $val,
                            ]
                        )
                    )
                );
            }
        }
        return $item;
    }

    /**
     * @throws HelperException
     */
    protected function createWriterFieldTag(array $field): WriterTag
    {
        $tag = new WriterTag('field', ['name' => $field['FIELD_NAME']]);

        if ($field['USER_TYPE_ID'] == 'enumeration') {
            $this->writeFieldEnumeration($tag, $field);
        } elseif ($field['USER_TYPE_ID'] == 'iblock_element') {
            $this->writeFieldIblockElement($tag, $field);
        } elseif ($field['USER_TYPE_ID'] == 'iblock_section') {
            $this->writeFieldIblockSection($tag, $field);
        } elseif ($field['USER_TYPE_ID'] == 'hlblock') {
            $this->writeFieldHlblockElement($tag, $field);
        } elseif ($field['USER_TYPE_ID'] == 'file') {
            $tag->addFile($field['VALUE'], $field['MULTIPLE'] == 'Y');
        } else {
            $tag->addValue($field['VALUE'], $field['MULTIPLE'] == 'Y');
        }

        return $tag;
    }

    /**
     * @throws HelperException
     */
    protected function writeFieldEnumeration(WriterTag $tag, array $field): void
    {
        $xmlIds = $this->getFieldEnumXmlIdsByIds(
            $field['HLBLOCK_ID'],
            $field['FIELD_NAME'],
            $field['VALUE']
        );
        $tag->addValue($xmlIds, true);
    }

    /**
     * @throws HelperException
     */
    protected function writeFieldIblockElement(WriterTag $tag, array $field): void
    {
        $linkedIblockId = $field['SETTINGS']['IBLOCK_ID'] ?? '';
        if (!empty($field['VALUE']) && !empty($linkedIblockId)) {
            foreach ($this->makeNonEmptyArray($field['VALUE']) as $elementId) {
                (new IblockExchangeHelper())->writeValueIblockElement(
                    $tag,
                    $linkedIblockId,
                    $elementId
                );
            }
        }
    }

    /**
     * @throws HelperException
     */
    protected function writeFieldIblockSection(WriterTag $tag, array $field): void
    {
        $linkedIblockId = $field['SETTINGS']['IBLOCK_ID'] ?? '';
        if (!empty($field['VALUE']) && !empty($linkedIblockId)) {
            foreach ($this->makeNonEmptyArray($field['VALUE']) as $elementId) {
                (new IblockExchangeHelper())->writeValueIblockSection(
                    $tag,
                    $linkedIblockId,
                    $elementId
                );
            }
        }
    }

    /**
     * @throws HelperException
     */
    protected function writeFieldHlblockElement(WriterTag $tag, array $field): void
    {
        $linkedHlblockId = $field['SETTINGS']['HLBLOCK_ID'] ?? '';
        if (!empty($field['VALUE']) && !empty($linkedHlblockId)) {
            foreach ($this->makeNonEmptyArray($field['VALUE']) as $elementId) {
                $this->writeValueHlblockElement($tag, $linkedHlblockId, $elementId);
            }
        }
    }

    /**
     * @throws HelperException
     */
    public function writeValueHlblockElement(WriterTag $tag, int $hlblockId, int $elementId): void
    {
        $item = $this->getElementIfExists($hlblockId, ['ID' => $elementId]);

        // непонятно какие уникальные поля записывать в атриубты, мб обязательные (MANDATORY) ?

        $tag->addValueTag($item['ID'], array_filter([
            'uf_xml_id' => $item['UF_XML_ID'] ?? '',
        ]));
    }
}
