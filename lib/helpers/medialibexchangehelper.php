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
     * @param $typeId
     * @param $path
     *
     * @return int|void
     * @throws HelperException
     */
    public function saveCollectionByPath($typeId, $path)
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

    /**
     * @throws HelperException
     */
    public function getElementsExchangeDto($collectionId, $params = [], $exportFields = []): ExchangeDto
    {
        $elements = $this->getElements($collectionId, $params);

        $dto = new ExchangeDto('tmp');
        foreach ($elements as $element) {

            $item = new ExchangeDto('item');
            foreach ($element as $code => $val) {
                if (!in_array($code, $exportFields)) {
                    continue;
                }

                $field = $this->createFieldDto([
                    'NAME' => $code,
                    'VALUE' => $val,
                ]);


                $item->addChild($field);
            }
            $dto->addChild($item);
        }
        return $dto;
    }

    private function createFieldDto(array $field): ExchangeDto
    {
        $dto = new ExchangeDto('field', ['name' => $field['NAME']]);

        if ($field['NAME'] == 'SOURCE_ID') {
            $dto->setAttribute('name', 'FILE');
            $dto->addFile($field['VALUE']);
        } elseif ($field['NAME'] == 'COLLECTION_ID') {
            $path = $this->getCollectionPath(self::TYPE_IMAGE, $field['VALUE']);
            $dto->setAttribute('name', 'COLLECTION_PATH');
            $dto->addValue($path);
        } else {
            $dto->addValue($field['VALUE']);
        }

        return $dto;
    }


}
