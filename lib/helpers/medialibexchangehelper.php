<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\Base\ExchangeDto;

class MedialibExchangeHelper extends MedialibHelper
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

    /**
     * @throws HelperException
     */
    public function createRecordsDto($collectionId, int $offset, int $limit, array $exportFields): ExchangeDto
    {
        $elements = $this->getElements(
            $collectionId,
            [
                'offset' => $offset,
                'limit' => $limit,
            ],
        );

        $dto = new ExchangeDto('tmp');
        foreach ($elements as $element) {
            $dto->addChild(
                $this->createRecordDto(
                    $element,
                    $exportFields,
                )
            );
        }
        return $dto;
    }


    private function createRecordDto(array $element, array $exportFields): ExchangeDto
    {
        $item = new ExchangeDto('item');
        foreach ($element as $code => $val) {
            if (in_array($code, $exportFields)) {
                $item->addChild(
                    $this->createFieldDto([
                        'NAME' => $code,
                        'VALUE' => $val,
                    ])
                );
            }
        }
        return $item;
    }

    private function createFieldDto(array $field): ExchangeDto
    {
        $dto = new ExchangeDto('field', ['name' => $field['NAME']]);

        if ($field['NAME'] == 'SOURCE_ID') {
            $dto->setAttribute('name', 'FILE');
            $dto->addFile($field['VALUE']);
        } elseif ($field['NAME'] == 'COLLECTION_ID') {
            $path = $this->getCollectionPath(
                self::TYPE_IMAGE,
                $field['VALUE']
            );
            $dto->setAttribute('name', 'COLLECTION_PATH');
            $dto->addValue($path);
        } else {
            $dto->addValue($field['VALUE']);
        }

        return $dto;
    }

    //reader

    /**
     * @throws HelperException
     */
    public function convertRecord(array $record): array
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

    protected function convertFieldValue(array $field): string
    {
        return $field['value'][0]['value'];
    }
}
