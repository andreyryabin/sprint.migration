<?php

namespace Sprint\Migration\Exchange\Helpers;

use CIBlockElement;


class IblockExchangeHelper extends ExchangeHelper
{
    protected $cachedProps = [];

    public function isEnabled()
    {
        return $this->getHelperManager()
            ->Iblock()
            ->isEnabled();
    }

    public function getIblockIdByUid($iblockUid)
    {
        return $this->getHelperManager()
            ->Iblock()
            ->getIblockIdByUid($iblockUid);
    }

    public function getIblockUid($iblockId)
    {
        return $this->getHelperManager()
            ->Iblock()
            ->getIblockUid($iblockId);
    }

    public function getPropertyType($iblockId, $code)
    {
        $prop = $this->getCachedProperty($iblockId, $code);
        return $prop['PROPERTY_TYPE'];
    }

    public function isPropertyMultiple($iblockId, $code)
    {
        $prop = $this->getCachedProperty($iblockId, $code);
        return ($prop['MULTIPLE'] == 'Y');
    }

    public function getPropertyEnumIdByXmlId($iblockId, $code, $xmlId)
    {
        $prop = $this->getCachedProperty($iblockId, $code);
        if (empty($prop['VALUES']) || !is_array($prop['VALUES'])) {
            return '';
        }

        foreach ($prop['VALUES'] as $val) {
            if ($val['XML_ID'] == $xmlId) {
                return $val['ID'];
            }
        }
        return '';
    }

    /**
     * @param $iblockId
     * @param $offset
     * @param $limit
     * @return array
     */
    public function getElements($iblockId, $offset, $limit)
    {
        $pageNum = (int)floor($offset / $limit) + 1;

        $dbres = CIBlockElement::GetList([
            'ID' => 'ASC',
        ], [
            'IBLOCK_ID' => $iblockId,
            'CHECK_PERMISSIONS' => 'N',
        ], false, [
            'nPageSize' => $limit,
            'iNumPage' => $pageNum,
            'checkOutOfRange' => true,
        ]);

        $result = [];
        while ($item = $dbres->GetNextElement(false, false)) {
            $result[] = [
                'FIELDS' => $item->GetFields(),
                'PROPS' => $item->GetProperties(),
            ];
        }
        return $result;
    }

    public function getElementsCount($iblockId)
    {
        return $this->getHelperManager()
            ->Iblock()
            ->getElementsCount($iblockId);
    }

    protected function getCachedProperty($iblockId, $code)
    {
        $key = $iblockId . $code;

        if (!isset($this->cachedProps[$key])) {
            $this->cachedProps[$key] = $this->getHelperManager()
                ->Iblock()
                ->getProperty($iblockId, $code);
        }
        return $this->cachedProps[$key];
    }
}