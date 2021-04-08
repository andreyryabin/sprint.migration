<?php

namespace Sprint\Migration\Helpers;

use CIBlockElement;

class IblockExchangeHelper extends IblockHelper
{
    protected $cachedProps = [];

    /**
     * @param int   $iblockId
     * @param array $params
     *
     * @return array
     */
    public function getElementsEx($iblockId, $params = [])
    {
        $params = array_merge(
            [
                'offset' => 0,
                'limit'  => 0,
                'filter' => [],
                'order'  => ['ID' => 'ASC'],
            ], $params
        );

        $pageNum = $this->getPageNumberFromOffset($params['offset'], $params['limit']);

        $params['filter']['IBLOCK_ID'] = $iblockId;
        $params['filter']['CHECK_PERMISSIONS'] = 'N';

        $dbres = CIBlockElement::GetList(
            $params['order'],
            $params['filter'],
            false,
            [
                'nPageSize'       => $params['limit'],
                'iNumPage'        => $pageNum,
                'checkOutOfRange' => true,
            ]
        );

        $result = [];
        while ($item = $dbres->GetNextElement(false, false)) {
            $fields = $item->GetFields();
            $props = $item->GetProperties();

            $fields['IBLOCK_SECTION'] = $this->getElementSectionIds($fields['ID']);

            $result[] = [
                'FIELDS' => $fields,
                'PROPS'  => $props,
            ];
        }
        return $result;
    }

    public function getProperty($iblockId, $code)
    {
        $key = $iblockId . $code;

        if (!isset($this->cachedProps[$key])) {
            $this->cachedProps[$key] = parent::getProperty($iblockId, $code);
        }
        return $this->cachedProps[$key];
    }

    protected function getPageCountFromElementsCount($total, $limit)
    {
        return (int)ceil($total / $limit);
    }

    protected function getPageNumberFromOffset($offset, $limit)
    {
        return (int)floor($offset / $limit) + 1;
    }

    protected function getOffsetFromPageNumber($pageNumber, $limit)
    {
        $pageNumber = (int)$pageNumber;
        $pageNumber = ($pageNumber < 1) ? 1 : $pageNumber;

        return ($pageNumber - 1) * $limit;
    }
}
