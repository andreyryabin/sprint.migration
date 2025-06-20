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
     * @param $hlblockName
     * @param $fieldName
     *
     * @throws HelperException
     * @return mixed
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
        return array_map(
            fn($hlblock) => [
                'title' => Locale::getMessage('HLBLOCK_TITLE', $hlblock),
                'value' => $hlblock['ID'],
            ],
            $this->getHlblocks()
        );
    }

    /**
     * @throws HelperException
     */
    public function getHlblockFieldsStructure($hlblockName): array
    {
        return array_map(
            fn($field) => [
                'title' => Locale::getMessage('HLBLOCK_FIELD', $field),
                'value' => $field['FIELD_NAME'],
            ],
            $this->getFields($hlblockName)
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


    //writer

    /**
     * @throws HelperException
     */
    public function getWriterAttributes(...$vars): array
    {
        [$hlblockId] = $vars;

        return [
            'hlblockUid' => $this->getHlblockUid($hlblockId),
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
    private function createWriterRecordTag($hlblockId, array $element, array $exportFields): WriterTag
    {
        $item = new WriterTag('item');

        foreach ($element as $code => $val) {
            if (in_array($code, $exportFields)) {
                $item->addChild(
                    $this->createWriterFieldTag([
                        'NAME'         => $code,
                        'VALUE'        => $val,
                        'HLBLOCK_ID'   => $hlblockId,
                        'USER_TYPE_ID' => $this->getFieldType($hlblockId, $code),
                        'MULTIPLE'     => $this->isFieldMultiple($hlblockId, $code),
                    ])
                );
            }
        }
        return $item;
    }

    /**
     * @throws HelperException
     */
    private function createWriterFieldTag(array $field): WriterTag
    {
        $tag = new WriterTag('field', ['name' => $field['NAME']]);

        if ($field['USER_TYPE_ID'] == 'enumeration') {
            $xmlIds = $this->getFieldEnumXmlIdsByIds(
                $field['HLBLOCK_ID'],
                $field['NAME'],
                $field['VALUE']
            );
            $tag->addValue($xmlIds, true);
        } elseif ($field['USER_TYPE_ID'] == 'file') {
            $tag->addFile($field['VALUE'], $field['MULTIPLE']);
        } else {
            $tag->addValue($field['VALUE'], $field['MULTIPLE']);
        }

        return $tag;
    }
}
