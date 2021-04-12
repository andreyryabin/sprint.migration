<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Exceptions\HelperException;

class HlblockExchangeHelper extends HlblockHelper
{
    protected $cachedFields = [];

    /**
     * @param $hlblockName
     * @param $fieldName
     *
     * @throws HelperException
     * @return mixed
     */
    public function getField($hlblockName, $fieldName)
    {
        $key = $hlblockName . $fieldName;

        if (!isset($this->cachedProps[$key])) {
            $this->cachedFields[$key] = parent::getField($hlblockName, $fieldName);
        }
        return $this->cachedFields[$key];
    }

    /**
     * @throws HelperException
     * @return array
     */
    public function getHlblocksStructure()
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
     * @param $hlblockName
     *
     * @throws HelperException
     * @return array
     */
    public function getHlblockFieldsCodes($hlblockName)
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
}
