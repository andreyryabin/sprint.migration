<?php

namespace Sprint\Migration\Exchange\Helpers;

use CIBlockElement;
use CIBlockSection;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Locale;

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
        $prop = $this->getProperty($iblockId, $code);
        return $prop['PROPERTY_TYPE'];
    }

    public function getPropertyLinkIblockId($iblockId, $code)
    {
        $prop = $this->getProperty($iblockId, $code);
        return $prop['LINK_IBLOCK_ID'];
    }

    public function isPropertyMultiple($iblockId, $code)
    {
        $prop = $this->getProperty($iblockId, $code);
        return ($prop['MULTIPLE'] == 'Y');
    }

    public function getPropertyEnumIdByXmlId($iblockId, $code, $xmlId)
    {
        $prop = $this->getProperty($iblockId, $code);
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
     * @param int   $iblockId
     * @param int   $offset
     * @param int   $limit
     * @param array $filter
     *
     * @return array
     */
    public function getElements($iblockId, $offset, $limit, $filter = [])
    {
        $pageNum = $this->getPageNumberFromOffset($offset, $limit);

        $filter['IBLOCK_ID'] = $iblockId;
        $filter['CHECK_PERMISSIONS'] = 'N';

        $dbres = CIBlockElement::GetList(
            [
                'ID' => 'ASC',
            ], $filter,
            false,
            [
                'nPageSize'       => $limit,
                'iNumPage'        => $pageNum,
                'checkOutOfRange' => true,
            ]
        );

        $result = [];
        while ($item = $dbres->GetNextElement(false, false)) {
            $fields = $item->GetFields();
            $props = $item->GetProperties();

            $fields['IBLOCK_SECTION'] =
                $this->getHelperManager()
                     ->Iblock()
                     ->getElementSectionIds($fields['ID']);

            $result[] = [
                'FIELDS' => $fields,
                'PROPS'  => $props,
            ];
        }
        return $result;
    }

    public function getElementsCount($iblockId, $filter = [])
    {
        return $this->getHelperManager()
                    ->Iblock()
                    ->getElementsCount($iblockId, $filter);
    }

    /**
     * @param $iblockId
     * @param $sectionId
     *
     * @throws HelperException
     * @return string
     */
    public function getSectionUniqNameById($iblockId, $sectionId)
    {
        if (empty($sectionId)) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_IB_SECTION_ID_EMPTY',
                    [
                        '#IBLOCK_ID#' => $iblockId,
                    ]
                )
            );
        }

        $section = CIBlockSection::GetList(
            [],
            [
                'ID'        => $sectionId,
                'IBLOCK_ID' => $iblockId,
            ]
        )->Fetch();

        if (empty($section['ID'])) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_IB_SECTION_ID_NOT_FOUND',
                    [
                        '#IBLOCK_ID#'  => $iblockId,
                        '#SECTION_ID#' => $sectionId,
                    ]
                )
            );
        }

        return $section['NAME'] . '|' . (int)$section['DEPTH_LEVEL'];
    }

    /**
     * @param $iblockId
     * @param $uniqName
     *
     * @throws HelperException
     * @return int|mixed|string
     */
    public function getSectionIdByUniqName($iblockId, $uniqName)
    {
        if (empty($uniqName)) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_IB_SECTION_ID_EMPTY',
                    [
                        '#IBLOCK_ID#' => $iblockId,
                    ]
                )
            );
        }

        if (is_numeric($uniqName)) {
            return $uniqName;
        }

        list($sectionName, $depthLevel) = explode('|', $uniqName);

        $section = CIBlockSection::GetList(
            [],
            [
                'NAME'        => $sectionName,
                'DEPTH_LEVEL' => $depthLevel,
                'IBLOCK_ID'   => $iblockId,
            ]
        )->Fetch();

        if (empty($section['ID'])) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_IB_SECTION_ON_LEVEL_NOT_FOUND',
                    [
                        '#SECTION_NAME#' => $sectionName,
                        '#DEPTH_LEVEL#'  => $depthLevel,
                    ]
                )
            );
        }

        return $section['ID'];
    }

    /**
     * @param       $iblockId
     * @param array $sectionIds
     *
     * @throws HelperException
     * @return array
     */
    public function getSectionUniqNamesByIds($iblockId, $sectionIds = [])
    {
        $uniqNames = [];

        if (empty($sectionIds) || !is_array($sectionIds)) {
            return $uniqNames;
        }

        foreach ($sectionIds as $sectionId) {
            $uniqNames[] = $this->getSectionUniqNameById($iblockId, $sectionId);
        }

        return $uniqNames;
    }

    /**
     * @param       $iblockId
     * @param array $uniqNames
     *
     * @throws HelperException
     * @return array
     */
    public function getSectionIdsByUniqNames($iblockId, $uniqNames = [])
    {
        $ids = [];

        if (empty($uniqNames) || !is_array($uniqNames)) {
            return $ids;
        }

        foreach ($uniqNames as $uniqName) {
            $ids[] = $this->getSectionIdByUniqName($iblockId, $uniqName);
        }
        return $ids;
    }

    public function getProperty($iblockId, $code)
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
