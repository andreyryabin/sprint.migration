<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\Base\ExchangeReader;
use Sprint\Migration\Helpers\IblockExchangeHelper;

class IblockElementsImport extends ExchangeReader
{
    protected $iblockId;

    public function setIblockId($iblockId): static
    {
        $this->iblockId = $iblockId;
        return $this;
    }
    /**
     * @throws HelperException
     */
    protected function convertRecord($record): array
    {
        $iblockExchangeHelper = new IblockExchangeHelper();
        $iblockId = $iblockExchangeHelper->getIblockIdByUid($this->iblockId);


        $convertedFields = [];
        foreach ($record['fields'] as $field) {
            if (in_array($field['name'], ['PREVIEW_PICTURE', 'DETAIL_PICTURE'])) {
                $convertedFields[$field['name']] = $this->convertFieldF($iblockId, $field);
            } elseif ($field['name'] == 'IBLOCK_SECTION') {
                $convertedFields[$field['name']] = $this->convertFieldIblockSection($iblockId, $field);
            } else {
                $convertedFields[$field['name']] = $this->convertFieldS($iblockId, $field);
            }
        }

        $convertedProperties = [];
        foreach ($record['properties'] as $prop) {
            $proprtyType = $iblockExchangeHelper->getPropertyType($iblockId, $prop['name']);

            if ($proprtyType == 'L') {
                $convertedProperties[$prop['name']] = $this->convertPropertyL($iblockId, $prop);
            } elseif ($proprtyType == 'F') {
                $convertedProperties[$prop['name']] = $this->convertPropertyF($iblockId, $prop);
            } elseif ($proprtyType == 'G') {
                $convertedProperties[$prop['name']] = $this->convertPropertyG($iblockId, $prop);
            } elseif ($proprtyType == 'E') {
                $convertedProperties[$prop['name']] = $this->convertPropertyE($iblockId, $prop);
            } else {
                $convertedProperties[$prop['name']] = $this->convertPropertyS($iblockId, $prop);
            }
        }

        return [
            'iblock_id' => $iblockId,
            'fields' => $convertedFields,
            'properties' => $convertedProperties,
        ];
    }

    protected function convertFieldS(int $iblockId, array $field): string
    {
        return $this->makeFieldValue($field['value'][0]);
    }

    protected function makeFieldValue($val): string
    {
        return (string)$val['value'];
    }

    /**
     * @throws HelperException
     */
    protected function convertFieldIblockSection(int $iblockId, array $field): array
    {
        $iblockExchangeHelper = new IblockExchangeHelper();

        $value = [];
        foreach ($field['value'] as $val) {
            $val['value'] = $iblockExchangeHelper->getSectionIdByUniqName($iblockId, $val['value']);
            $value[] = $this->makeFieldValue($val);
        }

        return $value;
    }

    protected function convertFieldF(int $iblockId, array $field): false|array
    {
        return $this->makeFileValue($field['value'][0]);
    }

    protected function convertPropertyS(int $iblockId, array $prop)
    {
        $iblockExchangeHelper = new IblockExchangeHelper();

        $isMultiple = $iblockExchangeHelper->isPropertyMultiple($iblockId, $prop['name']);
        $res = [];
        foreach ($prop['value'] as $val) {
            $res[] = $this->makePropertyValue($val);
        }

        return ($isMultiple) ? $res : $res[0];
    }

    protected function makePropertyValue(array $val): array
    {
        $result = ['VALUE' => $val['value']];

        if (!empty($val['description'])) {
            $result['DESCRIPTION'] = $val['description'];
        }

        return $result;
    }

    /**
     * @throws HelperException
     */
    protected function convertPropertyG(int $iblockId, array $prop)
    {
        $iblockExchangeHelper = new IblockExchangeHelper();

        $isMultiple = $iblockExchangeHelper->isPropertyMultiple($iblockId, $prop['name']);
        $linkIblockId = $iblockExchangeHelper->getPropertyLinkIblockId($iblockId, $prop['name']);

        $res = [];
        if ($linkIblockId) {
            foreach ($prop['value'] as $val) {
                $val['value'] = $iblockExchangeHelper->getSectionIdByUniqName($linkIblockId, $val['value']);
                $res[] = $this->makePropertyValue($val);
            }
        }

        return ($isMultiple) ? $res : $res[0];
    }

    /**
     * @throws HelperException
     */
    protected function convertPropertyE(int $iblockId, array $prop)
    {
        $iblockExchangeHelper = new IblockExchangeHelper();

        $isMultiple = $iblockExchangeHelper->isPropertyMultiple($iblockId, $prop['name']);
        $linkIblockId = $iblockExchangeHelper->getPropertyLinkIblockId($iblockId, $prop['name']);

        $res = [];
        if ($linkIblockId) {
            foreach ($prop['value'] as $val) {
                $val['value'] = $iblockExchangeHelper->getElementIdByUniqName($linkIblockId, $val['value']);
                $res[] = $this->makePropertyValue($val);
            }
        }

        return ($isMultiple) ? $res : $res[0];
    }

    protected function convertPropertyF(int $iblockId, array $prop)
    {
        $iblockExchangeHelper = new IblockExchangeHelper();

        $isMultiple = $iblockExchangeHelper->isPropertyMultiple($iblockId, $prop['name']);
        $res = [];
        foreach ($prop['value'] as $val) {
            $res[] = $this->makeFileValue($val);
        }
        return ($isMultiple) ? $res : $res[0];
    }

    protected function convertPropertyL(int $iblockId, array $prop)
    {
        $iblockExchangeHelper = new IblockExchangeHelper();

        $isMultiple = $iblockExchangeHelper->isPropertyMultiple($iblockId, $prop['name']);
        $res = [];
        foreach ($prop['value'] as $val) {
            $val['value'] = $iblockExchangeHelper->getPropertyEnumIdByXmlId(
                $iblockId,
                $prop['name'],
                $val['value']
            );

            $res[] = $this->makePropertyValue($val);
        }
        return ($isMultiple) ? $res : $res[0];
    }
}
