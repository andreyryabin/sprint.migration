<?php

namespace Sprint\Migration\Helpers;

/**
 * Class MedialibExchangeHelper
 *
 * @package Sprint\Migration\Helpers
 */
class MedialibExchangeHelper extends MedialibHelper
{
    private $cachedFlatTree = null;

    public function getCollectionsFlatTree($typeId)
    {
        if (is_null($this->cachedFlatTree)) {
            $this->cachedFlatTree = parent::getCollectionsFlatTree($typeId);
        }
        return $this->cachedFlatTree;
    }

    public function getCollectionStructure()
    {
        $items = $this->getCollectionsFlatTree($this::TYPE_IMAGE);

        $res = [];
        foreach ($items as $item) {
            $res[] = [
                'title' => $item['DEPTH_NAME'],
                'value' => $item['ID'],
            ];
        }
        return $res;
    }

    public function getCollectionPath($collectionId)
    {
        foreach ($this->getCollectionsFlatTree($this::TYPE_IMAGE) as $item) {
            if ($item['ID'] == $collectionId) {
                return $item['PATH'];
            }
        }
        return [];
    }
}
