<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Exceptions\HelperException;

/**
 * Class MedialibExchangeHelper
 *
 * @package Sprint\Migration\Helpers
 */
class MedialibExchangeHelper extends MedialibHelper
{
    private $cachedFlatTree = [];
    private $cachedPaths    = [];

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
     * @throws HelperException
     * @return int|void
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
}
