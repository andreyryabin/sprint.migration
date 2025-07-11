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

    public function getCollectionsFlatTree(int|string $typeId): array
    {
        if (!isset($this->cachedFlatTree[$typeId])) {
            $this->cachedFlatTree[$typeId] = parent::getCollectionsFlatTree($typeId);
        }
        return $this->cachedFlatTree[$typeId];
    }

    /**
     * @throws HelperException
     */
    public function saveCollectionByPath(int|string $typeId, array $path): int
    {
        $uid = md5($typeId . implode('', $path));
        if (!isset($this->cachedPaths[$uid])) {
            $this->cachedPaths[$uid] = parent::saveCollectionByPath($typeId, $path);
        }
        return $this->cachedPaths[$uid];
    }

    /**
     * @throws HelperException
     */
    public function getCollectionStructure(int|string $typeId): array
    {
        return array_map(fn($item) => [
            'title' => $item['DEPTH_NAME'],
            'value' => $item['ID'],
        ], $this->getCollectionsFlatTree($typeId));
    }

    /**
     * @throws HelperException
     */
    public function getCollectionPath(int|string $typeId, int $collectionId)
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
                'limit'  => $limit,
            ],
        );

        $tag = new WriterTag('tmp');
        foreach ($elements as $element) {
            $tag->addChild(
                $this->createWriterRecordTag(
                    $element,
                    $exportFields,
                )
            );
        }
        return $tag;
    }


    /**
     * @throws HelperException
     */
    private function createWriterRecordTag(array $element, array $exportFields): WriterTag
    {
        $item = new WriterTag('item');
        foreach ($element as $code => $val) {
            if (in_array($code, $exportFields)) {
                $item->addChild(
                    $this->createWriterFieldTag([
                        'NAME'  => $code,
                        'VALUE' => $val,
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

        if ($field['NAME'] == 'SOURCE_ID') {
            $tag->setAttribute('name', 'FILE');
            $tag->addFile($field['VALUE'], false);
        } elseif ($field['NAME'] == 'COLLECTION_ID') {
            $tag->setAttribute('name', 'COLLECTION_PATH');
            $tag->addValue($this->getCollectionPath(self::TYPE_IMAGE, $field['VALUE']), true);
        } else {
            $tag->addValue($field['VALUE'], false);
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
                $fields['COLLECTION_ID'] = $this->readFieldCollectionPath($field);
            } else {
                $fields[$field['name']] = $this->readFieldValue($field);
            }
        }
        return $fields;
    }

    /**
     * @throws HelperException
     */
    protected function readFieldCollectionPath(array $field): int
    {
        $paths = array_column($field['value'], 'value');
        return $this->saveCollectionByPath(self::TYPE_IMAGE, $paths);
    }

    protected function readFieldValue(array $field)
    {
        return $field['value'][0]['value'];
    }
}
