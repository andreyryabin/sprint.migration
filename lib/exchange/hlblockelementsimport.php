<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\Base\ExchangeReader;
use Sprint\Migration\Helpers\HlblockExchangeHelper;

class HlblockElementsImport extends ExchangeReader
{
    protected $hlblockId;

    public function setHlblockId($hlblockId): static
    {
        $this->hlblockId = $hlblockId;
        return $this;
    }
    /**
     * @throws HelperException
     */
    protected function convertRecord(array $record): array
    {
        $hblockExchange = new HlblockExchangeHelper();
        $hlblockId = $hblockExchange->getHlblockIdByUid($this->hlblockId);

        $convertedFields = [];
        foreach ($record['fields'] as $field) {
            $fieldType = $hblockExchange->getFieldType($hlblockId, $field['name']);

            if ($fieldType == 'enumeration') {
                $convertedFields[$field['name']] = $this->convertFieldEnumeration($hlblockId, $field);
            } elseif ($fieldType == 'file') {
                $convertedFields[$field['name']] = $this->convertFieldFile($hlblockId, $field);
            } else {
                $convertedFields[$field['name']] = $this->convertFieldString($hlblockId, $field);
            }

        }

        return [
            'hlblock_id' => $hlblockId,
            'fields' => $convertedFields,
        ];
    }

    /**
     * @throws HelperException
     */
    protected function convertFieldString(int $hlblockId, array $field)
    {
        $hblockExchange = new HlblockExchangeHelper();
        if ($hblockExchange->isFieldMultiple($hlblockId, $field['name'])) {
            $res = [];
            foreach ($field['value'] as $val) {
                $res[] = $val['value'];
            }
            return $res;
        } else {
            return $field['value'][0]['value'];
        }
    }

    /**
     * @throws HelperException
     */
    protected function convertFieldFile(int $hlblockId, array $field): false|array
    {
        $hblockExchange = new HlblockExchangeHelper();
        if ($hblockExchange->isFieldMultiple($hlblockId, $field['name'])) {
            $res = [];
            foreach ($field['value'] as $val) {
                $res[] = $this->makeFileValue($val);
            }
            return $res;
        } else {
            return $this->makeFileValue($field['value'][0]);
        }
    }

    /**
     * @throws HelperException
     */
    protected function convertFieldEnumeration(int $hlblockId, array $field)
    {
        $hblockExchange = new HlblockExchangeHelper();
        if ($hblockExchange->isFieldMultiple($hlblockId, $field['name'])) {
            $res = [];
            foreach ($field['value'] as $val) {
                $res[] = $hblockExchange->getFieldEnumIdByXmlId(
                    $hlblockId,
                    $field['name'],
                    $val['value']
                );
            }
            return $res;
        } else {
            return $hblockExchange->getFieldEnumIdByXmlId(
                $hlblockId,
                $field['name'],
                $field['value'][0]['value']
            );
        }
    }
}
