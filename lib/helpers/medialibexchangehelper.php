<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\ExchangeDto;

/**
 * Class MedialibExchangeHelper
 *
 * @package Sprint\Migration\Helpers
 */
class MedialibExchangeHelper extends MedialibHelper
{
    private $cachedFlatTree = [];
    private $cachedPaths = [];

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

    public function getCollectionStructure($typeId)
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
                if (in_array($code, $exportFields)) {

                    $field = new ExchangeDto('field');
                    if ($code == 'SOURCE_ID') {
                        $field->setAttribute('name', 'FILE');
                        $field->addFile($val);
                    } elseif ($code == 'COLLECTION_ID') {
                        $field->setAttribute('name', 'COLLECTION_PATH');
                        $field->addValue($this->getCollectionPath(self::TYPE_IMAGE, $val));
                    } else {
                        $field->setAttribute('name', $code);
                        $field->addValue($val);
                    }

                    $item->addChild($field);
                }
            }

            $dto->addChild($item);
        }

        return $dto;
    }


}
