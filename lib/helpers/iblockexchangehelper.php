<?php

namespace Sprint\Migration\Helpers;

use _CIBElement;
use Sprint\Migration\Exceptions\HelperException;

class IblockExchangeHelper extends IblockHelper
{
    protected $cachedProps = [];

    public function getProperty($iblockId, $code)
    {
        $key = $iblockId . $code;

        if (!isset($this->cachedProps[$key])) {
            $this->cachedProps[$key] = parent::getProperty($iblockId, $code);
        }
        return $this->cachedProps[$key];
    }

    /**
     * Структура инфоблоков для построения выпадающего списка
     *
     * @return array
     */
    public function getIblocksStructure(): array
    {
        return $this->createIblocksStructure(
            $this->getIblockTypes(),
            $this->getIblocks()
        );
    }

    public function createIblocksStructure(array $iblockTypes, array $iblocks): array
    {
        $res = [];
        foreach ($iblockTypes as $iblockType) {
            $res[$iblockType['ID']] = [
                'title' => '[' . $iblockType['ID'] . '] ' . $iblockType['LANG'][LANGUAGE_ID]['NAME'],
                'items' => [],
            ];
        }

        foreach ($iblocks as $iblock) {
            if (!empty($iblock['CODE'])) {
                $res[$iblock['IBLOCK_TYPE_ID']]['items'][] = [
                    'title' => '[' . $iblock['CODE'] . '] ' . $iblock['NAME'],
                    'value' => $iblock['ID'],
                ];
            }
        }

        return $res;
    }

    /**
     * @param $iblockId
     *
     * @return array
     */
    public function getIblockPropertiesStructure($iblockId): array
    {
        $props = $this->exportProperties($iblockId);

        $res = [];
        foreach ($props as $prop) {
            $res[] = [
                'title' => '[' . $prop['CODE'] . '] ' . $prop['NAME'],
                'value' => $prop['CODE'],
            ];
        }
        return $res;
    }

    /**
     * @param $iblockId
     *
     * @return array
     */
    public function getIblockElementFieldsStructure($iblockId)
    {
        $fields = $this->exportIblockElementFields($iblockId);

        $res = [];
        foreach ($fields as $fieldName => $field) {
            $res[] = [
                'title' => '[' . $fieldName . '] ' . $field['NAME'],
                'value' => $fieldName,
            ];
        }
        return $res;
    }

    /**
     * @throws HelperException
     */
    public function getSectionIdByUniqName($iblockId, $uniqName)
    {
        if (is_numeric($uniqName)) {
            return $uniqName;
        }

        if (is_string($uniqName)) {
            [$sectionName, $depthLevel, $code] = explode('|', $uniqName);
            $uniqName = [];
            if ($sectionName) {
                $uniqName['NAME'] = $sectionName;
            }
            if ($depthLevel) {
                $uniqName['DEPTH_LEVEL'] = $depthLevel;
            }
            if ($code) {
                $uniqName['CODE'] = $code;
            }
        }

        return $this->getSectionIdByUniqFilter($iblockId, $uniqName);
    }

    /**
     * @throws HelperException
     */
    public function getElementIdByUniqName($iblockId, $uniqName)
    {
        if (is_numeric($uniqName)) {
            return $uniqName;
        }

        if (is_string($uniqName)) {
            [$elementName, $xmlId, $code] = explode('|', $uniqName);
            $uniqName = [];
            if ($elementName) {
                $uniqName['NAME'] = $elementName;
            }
            if ($xmlId) {
                $uniqName['XML_ID'] = $xmlId;
            }
            if ($code) {
                $uniqName['CODE'] = $code;
            }
        }

        return $this->getElementIdByUniqFilter($iblockId, $uniqName);
    }

    /**
     * @throws HelperException
     */
    public function getSectionUniqNameById($iblockId, $sectionId)
    {
        $filter = $this->getSectionUniqFilterById($iblockId, $sectionId);
        return $filter['NAME'] . '|' . $filter['DEPTH_LEVEL'] . '|' . $filter['CODE'];
    }

    /**
     * @throws HelperException
     */
    public function getElementUniqNameById($iblockId, $elementId)
    {
        $filter = $this->getElementUniqFilterById($iblockId, $elementId);
        return $filter['NAME'] . '|' . $filter['XML_ID'] . '|' . $filter['CODE'];
    }

    public function getElementFields(_CIBElement $element)
    {
        $fields = $element->GetFields();
        $fields['IBLOCK_SECTION'] = $this->getElementSectionIds($fields['ID']);
        return $fields;
    }

    public function getElementProps(_CIBElement $element)
    {
        return $element->GetProperties();
    }
}
