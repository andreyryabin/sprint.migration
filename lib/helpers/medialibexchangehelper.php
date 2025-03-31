<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\WriterTag;
use Sprint\Migration\Interfaces\ReaderHelperInterface;
use Sprint\Migration\Interfaces\WriterHelperInterface;

class MedialibExchangeHelper extends MedialibHelper implements ReaderHelperInterface, WriterHelperInterface
{
    private array $cachedFlatTree = [];
    private array $cachedPaths = [];

    public function getCollectionsFlatTree($typeId)
    {
        if (!isset($this->cachedFlatTree[$typeId])) {
            $this->cachedFlatTree[$typeId] = parent::getCollectionsFlatTree($typeId);
        }
        return $this->cachedFlatTree[$typeId];
    }

    /**
     * @throws HelperException
     */
    public function saveCollectionByPath($typeId, $path): int
    {
        $uid = md5($typeId . implode('', $path));
        if (!isset($this->cachedPaths[$uid])) {
            $this->cachedPaths[$uid] = parent::saveCollectionByPath($typeId, $path);
        }
        return $this->cachedPaths[$uid];
    }

    public function getCollectionStructure($typeId): array
    {
        $items = $this->getCollectionsFlatTree($typeId);

        $res = [];
        foreach ($items as $item) {
            $res[] = [
                'title' => $item['DEPTH_NAME'],
                'value' => $item['ID'],
            ];
        }
        return $res;
    }

    public function getCollectionPath($typeId, $collectionId)
    {
        foreach ($this->getCollectionsFlatTree($typeId) as $item) {
            if ($item['ID'] == $collectionId) {
                return $item['PATH'];
            }
        }
        return [];
    }


    //writer
    public function getWriterAttributes(...$vars): array
    {
        return [];
    }

    /**
     * @throws HelperException
     */
    public function getWriterRecordsCount(...$vars): int
    {
        [$collectionIds] = $vars;

        return $this->getElementsCount($collectionIds);
    }

    /**
     * @throws HelperException
     */
    public function getWriterRecordsTag(int $offset, int $limit, ...$vars): WriterTag
    {
        [$collectionId, $exportFields] = $vars;

        $elements = $this->getElements(
            $collectionId,
            [
                'offset' => $offset,
                'limit' => $limit,
            ],
        );

        $tag = new WriterTag('tmp');
        foreach ($elements as $element) {
            $tag->addChild(
                $this->createRecordTag(
                    $element,
                    $exportFields,
                )
            );
        }
        return $tag;
    }


    private function createRecordTag(array $element, array $exportFields): WriterTag
    {
        $item = new WriterTag('item');
        foreach ($element as $code => $val) {
            if (in_array($code, $exportFields)) {
                $item->addChild(
                    $this->createFieldTag([
                        'NAME' => $code,
                        'VALUE' => $val,
                    ])
                );
            }
        }
        return $item;
    }

    private function createFieldTag(array $field): WriterTag
    {
        $tag = new WriterTag('field', ['name' => $field['NAME']]);

        if ($field['NAME'] == 'SOURCE_ID') {
            $tag->setAttribute('name', 'FILE');
            $tag->addFile($field['VALUE'], false);
        } elseif ($field['NAME'] == 'COLLECTION_ID') {
            $path = $this->getCollectionPath(
                self::TYPE_IMAGE,
                $field['VALUE']
            );
            $tag->setAttribute('name', 'COLLECTION_PATH');
            $tag->addValue($path, false);
        } else {
            $tag->addValue($field['VALUE'],false);
        }

        return $tag;
    }

    //reader

    /**
     * @throws HelperException
     */
    public function convertReaderRecords(array $attributes, array $records): array
    {
        return array_map(fn($record) => $this->convertReaderRecord($record), $records);
    }

    /**
     * @throws HelperException
     */
    protected function convertReaderRecord(array $record): array
    {
        $fields = [];
        foreach ($record['fields'] as $field) {
            if ($field['name'] == 'COLLECTION_PATH') {
                $fields['COLLECTION_ID'] = $this->convertFieldCollectionPath($field);
            } else {
                $fields[$field['name']] = $this->convertFieldValue($field);
            }
        }
        return $fields;
    }

    /**
     * @throws HelperException
     */
    protected function convertFieldCollectionPath($field): int
    {
        $paths = array_column($field['value'], 'value');
        return $this->saveCollectionByPath(
            self::TYPE_IMAGE,
            $paths
        );
    }

    protected function convertFieldValue(array $field)
    {
        return $field['value'][0]['value'];
    }
}
