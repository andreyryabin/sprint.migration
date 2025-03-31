<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\WriterTag;
use Sprint\Migration\Interfaces\ReaderHelperInterface;
use Sprint\Migration\Interfaces\WriterHelperInterface;

class HlblockExchangeHelper extends HlblockHelper implements ReaderHelperInterface, WriterHelperInterface
{
    protected array $cachedFields = [];

    /**
     * @param $hlblockName
     * @param $fieldName
     *
     * @return mixed
     * @throws HelperException
     */
    public function getField($hlblockName, $fieldName): array
    {
        $key = $hlblockName . $fieldName;

        if (!isset($this->cachedProps[$key])) {
            $this->cachedFields[$key] = parent::getField($hlblockName, $fieldName);
        }
        return $this->cachedFields[$key];
    }

    /**
     * @throws HelperException
     */
    public function getHlblocksStructure(): array
    {
        $res = [];
        $hlblocks = $this->getHlblocks();
        foreach ($hlblocks as $hlblock) {
            $res[] = [
                'title' => $hlblock['NAME'],
                'value' => $hlblock['ID'],
            ];
        }
        return $res;
    }

    /**
     * @throws HelperException
     */
    public function getHlblockFieldsCodes($hlblockName): array
    {
        $res = [];
        $items = $this->getFields($hlblockName);
        foreach ($items as $item) {
            if (!empty($item['FIELD_NAME'])) {
                $res[] = $item['FIELD_NAME'];
            }
        }
        return $res;
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
                $convertedFields[$field['name']] = $this->convertFieldEnumeration($hlblockId, $field);
            } else {
                $convertedFields[$field['name']] = $this->convertFieldValue($hlblockId, $field);
            }
        }

        return [
            'hlblock_id' => $hlblockId,
            'fields' => $convertedFields,
        ];

    }

    /**
     * @throws HelperException
     */
    protected function convertFieldValue(int $hlblockId, array $field)
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
    protected function convertFieldEnumeration(int $hlblockId, array $field)
    {
        if ($this->isFieldMultiple($hlblockId, $field['name'])) {
            $res = [];
            foreach ($field['value'] as $val) {
                $res[] = $this->getFieldEnumIdByXmlId(
                    $hlblockId,
                    $field['name'],
                    $val['value']
                );
            }
            return $res;
        } else {
            return $this->getFieldEnumIdByXmlId(
                $hlblockId,
                $field['name'],
                $field['value'][0]['value']
            );
        }
    }


    //writer

    /**
     * @throws HelperException
     */
    public function getWriterAttributes(...$vars): array
    {
        [$hlblockId] = $vars;

        return [
            'hlblockUid' => $this->getHlblockUid($hlblockId)
        ];
    }

    /**
     * @throws HelperException
     */
    public function getWriterRecordsCount(...$vars): int
    {
        [$hlblockId] = $vars;

        return $this->getElementsCount($hlblockId);
    }

    /**
     * @throws HelperException
     */
    public function getWriterRecordsTag(int $offset, int $limit, ...$vars): WriterTag
    {
        [$hlblockId, $exportFields] = $vars;

        $elements = $this->getElements(
            $hlblockId,
            [
                'order' => ['ID' => 'ASC'],
                'offset' => $offset,
                'limit' => $limit,
            ]
        );

        $tag = new WriterTag('tmp');
        foreach ($elements as $element) {
            $tag->addChild(
                $this->createRecordTag(
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
    private function createRecordTag($hlblockId, array $element, array $exportFields): WriterTag
    {

        $item = new WriterTag('item');

        foreach ($element as $code => $val) {
            if (in_array($code, $exportFields)) {
                $item->addChild(
                    $this->createFieldTag([
                        'NAME' => $code,
                        'VALUE' => $val,
                        'HLBLOCK_ID' => $hlblockId,
                        'USER_TYPE_ID' => $this->getFieldType($hlblockId, $code),
                        'MULTIPLE' => $this->isFieldMultiple($hlblockId, $code),
                    ])
                );
            }
        }
        return $item;
    }

    /**
     * @throws HelperException
     */
    private function createFieldTag(array $field): WriterTag
    {
        $tag = new WriterTag('field', ['name' => $field['NAME']]);

        if ($field['USER_TYPE_ID'] == 'enumeration') {
            $xmlIds = $this->getFieldEnumXmlIdsByIds(
                $field['HLBLOCK_ID'],
                $field['NAME'],
                $field['VALUE']
            );
            $tag->addValue($xmlIds, $field['MULTIPLE']);
        } elseif ($field['USER_TYPE_ID'] == 'file') {
            $tag->addFile($field['VALUE'], $field['MULTIPLE']);
        } else {
            $tag->addValue($field['VALUE'], $field['MULTIPLE']);
        }

        return $tag;
    }


}
