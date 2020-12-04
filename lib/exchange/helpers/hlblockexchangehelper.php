<?php

namespace Sprint\Migration\Exchange\Helpers;

use Sprint\Migration\Exceptions\HelperException;

class HlblockExchangeHelper extends ExchangeHelper
{

    protected $cachedFields = [];

    public function isEnabled()
    {
        return $this->getHelperManager()
            ->Hlblock()
            ->isEnabled();
    }

    /**
     * @param $hlblockId
     * @throws HelperException
     * @return string
     */
    public function getHlblockUid($hlblockId)
    {
        return $this->getHelperManager()
            ->Hlblock()
            ->getHlblockUid($hlblockId);
    }

    /**
     * @param $hlblockId
     * @throws HelperException
     * @return int
     */
    public function getHlblockIdByUid($hlblockId)
    {
        return $this->getHelperManager()
            ->Hlblock()
            ->getHlblockIdByUid($hlblockId);
    }

    /**
     * @param $hlblockId
     * @param $offset
     * @param $limit
     * @throws HelperException
     * @return array
     */
    public function getElements($hlblockId, $offset, $limit)
    {
        return $this->getHelperManager()
            ->Hlblock()
            ->getElements($hlblockId, [
                'order' => ['ID' => 'ASC'],
                'offset' => $offset,
                'limit' => $limit,
            ]);
    }

    /**
     * @param $hlblockId
     * @throws HelperException
     * @return int
     */
    public function getElementsCount($hlblockId)
    {
        return $this->getHelperManager()
            ->Hlblock()
            ->getElementsCount($hlblockId);
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     * @throws HelperException
     * @return mixed
     */
    public function getFieldType($hlblockName, $fieldName)
    {
        $field = $this->getField($hlblockName, $fieldName);
        return $field['USER_TYPE_ID'];
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     * @throws HelperException
     * @return mixed
     */
    public function isFieldMultiple($hlblockName, $fieldName)
    {
        $field = $this->getField($hlblockName, $fieldName);
        return ($field['MULTIPLE'] == 'Y');
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     * @param $xmlId
     * @throws HelperException
     * @return mixed|string
     */
    public function getFieldEnumIdByXmlId($hlblockName, $fieldName, $xmlId)
    {
        $field = $this->getField($hlblockName, $fieldName);
        if (empty($field['ENUM_VALUES']) || !is_array($field['ENUM_VALUES'])) {
            return '';
        }

        foreach ($field['ENUM_VALUES'] as $val) {
            if ($val['XML_ID'] == $xmlId) {
                return $val['ID'];
            }
        }

        return '';
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     * @param $id
     * @throws HelperException
     * @return mixed|string
     */
    public function getFieldEnumXmlIdById($hlblockName, $fieldName, $id)
    {
        $field = $this->getField($hlblockName, $fieldName);
        if (empty($field['ENUM_VALUES']) || !is_array($field['ENUM_VALUES'])) {
            return '';
        }

        foreach ($field['ENUM_VALUES'] as $val) {
            if ($val['ID'] == $id) {
                return $val['XML_ID'];
            }
        }
        return '';
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     * @throws HelperException
     * @return mixed
     */
    public function getField($hlblockName, $fieldName)
    {
        $key = $hlblockName . $fieldName;

        if (!isset($this->cachedProps[$key])) {
            $this->cachedFields[$key] = $this->getHelperManager()
                ->Hlblock()
                ->getField($hlblockName, $fieldName);
        }
        return $this->cachedFields[$key];
    }
}
