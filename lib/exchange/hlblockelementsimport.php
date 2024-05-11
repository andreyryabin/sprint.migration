<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Locale;
use XMLReader;

class HlblockElementsImport extends AbstractExchange
{
    protected $converter;

    /**
     * @param callable $converter
     *
     * @throws RestartException
     * @throws HelperException
     */
    public function execute(callable $converter)
    {
        $hblockExchange = $this->getHelperManager()->HlblockExchange();

        $this->converter = $converter;
        $params = $this->exchangeEntity->getRestartParams();

        if (!isset($params['total'])) {
            if (!is_file($this->file)){
                throw new HelperException(
                    Locale::getMessage('ERR_EXCHANGE_FILE_NOT_FOUND', ['#FILE#' => $this->file])
                );
            }

            $reader = new XMLReader();
            $reader->open($this->getExchangeFile());
            $params['total'] = 0;
            $params['offset'] = 0;
            $hlblockUid = '';
            $exchangeVersion = 0;
            while ($reader->read()) {
                if ($this->isOpenTag($reader, 'items')) {
                    $exchangeVersion = (int)$reader->getAttribute('exchangeVersion');
                    $hlblockUid = $reader->getAttribute('hlblockUid');
                }
                if ($this->isOpenTag($reader, 'item')) {
                    $params['total']++;
                }
            }
            $reader->close();

            if (!$exchangeVersion || $exchangeVersion < self::EXCHANGE_VERSION) {
                throw new HelperException(
                    Locale::getMessage('ERR_EXCHANGE_VERSION', ['#NAME#' => $this->getExchangeFile()])
                );
            }

            $params['hlblock_id'] = $hblockExchange->getHlblockIdByUid($hlblockUid);
       }

        $reader = new XMLReader();
        $reader->open($this->getExchangeFile());
        $index = 0;

        while ($reader->read()) {
            if ($this->isOpenTag($reader, 'item')) {
                $collect = ($index >= $params['offset'] && $index < $params['offset'] + $this->getLimit());
                $restart = ($index >= $params['offset'] + $this->getLimit());
                $finish = ($index >= $params['total'] - 1);

                if ($collect) {
                    $this->collectItem($reader, $params['hlblock_id']);
                }

                if ($finish || $restart) {
                    $this->outProgress('', ($index + 1), $params['total']);
                }

                if ($restart) {
                    $params['offset'] = $index;
                    $this->exchangeEntity->setRestartParams($params);
                    $this->restart();
                }
                $index++;
            }
        }

        $reader->close();
        unset($params['offset']);
        unset($params['total']);
        unset($params['hlblock_id']);
        $this->exchangeEntity->setRestartParams($params);
    }

    /**
     * @param XMLReader $reader
     * @param           $hlblockId
     *
     * @throws HelperException
     */
    protected function collectItem(XMLReader $reader, $hlblockId)
    {
        $fields = [];
        if ($this->isOpenTag($reader, 'item')) {
            do {
                $reader->read();

                $field = $this->collectField($reader, 'field');
                if ($field) {
                    $fields[] = $field;
                }
            } while (!$this->isCloseTag($reader, 'item'));

            $convertedItem = $this->convertItem(
                [
                    'hlblock_id' => $hlblockId,
                    'fields'     => $fields,
                ]
            );

            if ($convertedItem) {
                call_user_func($this->converter, $convertedItem);
            }
        }
    }

    /**
     * @param $item
     *
     * @throws HelperException
     * @return array|bool
     */
    protected function convertItem($item)
    {
        if (empty($item['hlblock_id'])) {
            return false;
        }
        if (empty($item['fields'])) {
            return false;
        }

        $convertedFields = [];
        foreach ($item['fields'] as $field) {
            $method = $this->getConvertFieldMethod($item['hlblock_id'], $field['name']);
            if (method_exists($this, $method)) {
                $convertedFields[$field['name']] = $this->$method($item['hlblock_id'], $field);
            }
        }

        if (empty($convertedFields)) {
            return false;
        }

        return [
            'hlblock_id' => $item['hlblock_id'],
            'fields'     => $convertedFields,
        ];
    }

    /**
     * @param $hlblockId
     * @param $fieldName
     *
     * @throws HelperException
     * @return string
     */
    protected function getConvertFieldMethod($hlblockId, $fieldName)
    {
        $hblockExchange = $this->getHelperManager()->HlblockExchange();
        $type = $hblockExchange->getFieldType($hlblockId, $fieldName);

        if (in_array($type, ['enumeration', 'file'])) {
            return 'convertField' . ucfirst($type);
        } else {
            return 'convertFieldString';
        }
    }

    /**
     * @param $hlblockId
     * @param $field
     *
     * @throws HelperException
     * @return array
     */
    protected function convertFieldString($hlblockId, $field)
    {
        $hblockExchange = $this->getHelperManager()->HlblockExchange();
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
     * @param $hlblockId
     * @param $field
     *
     * @throws HelperException
     * @return array|bool|null
     */
    protected function convertFieldFile($hlblockId, $field)
    {
        $hblockExchange = $this->getHelperManager()->HlblockExchange();
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
     * @param $hlblockId
     * @param $field
     *
     * @throws HelperException
     * @return array
     */
    protected function convertFieldEnumeration($hlblockId, $field)
    {
        $hblockExchange = $this->getHelperManager()->HlblockExchange();
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
