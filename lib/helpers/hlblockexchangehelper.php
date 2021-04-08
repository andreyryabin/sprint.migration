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
}
