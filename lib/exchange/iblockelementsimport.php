<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Locale;
use XMLReader;

class IblockElementsImport extends AbstractExchange
{
    protected $converter;

    /**
     * @param callable $converter
     *
     * @throws HelperException
     * @throws RestartException
     */
    public function execute(callable $converter)
    {
        $iblockExchange = $this->getHelperManager()->IblockExchange();

        $this->converter = $converter;

        $params = $this->exchangeEntity->getRestartParams();

        if (!isset($params['total'])) {
            if (!is_file($this->file)) {
                throw new HelperException(
                    Locale::getMessage('ERR_EXCHANGE_FILE_NOT_FOUND', ['#FILE#' => $this->file])
                );
            }

            $reader = new XMLReader();
            $reader->open($this->getExchangeFile());
            $params['total'] = 0;
            $params['offset'] = 0;
            $exchangeVersion = 0;

            $iblockUid = '';

            while ($reader->read()) {
                if ($this->isOpenTag($reader, 'items')) {
                    $exchangeVersion = (int)$reader->getAttribute('exchangeVersion');
                    $iblockUid = $reader->getAttribute('iblockUid');
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

            $params['iblock_id'] = $iblockExchange->getIblockIdByUid($iblockUid);
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
                    $this->collectItem($reader, $params['iblock_id']);
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
        unset($params['iblock_id']);
        $this->exchangeEntity->setRestartParams($params);
    }

    /**
     * @param XMLReader $reader
     * @param           $iblockId
     */
    protected function collectItem(XMLReader $reader, $iblockId)
    {
        $fields = [];
        $props = [];

        if ($this->isOpenTag($reader, 'item')) {
            do {
                $reader->read();

                $field = $this->collectField($reader, 'field');
                if ($field) {
                    $fields[] = $field;
                }

                $prop = $this->collectField($reader, 'property');
                if ($prop) {
                    $props[] = $prop;
                }
            } while (!$this->isCloseTag($reader, 'item'));

            $convertedItem = $this->convertItem(
                [
                    'iblock_id'  => $iblockId,
                    'fields'     => $fields,
                    'properties' => $props,
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
     * @return array|bool
     */
    protected function convertItem($item)
    {
        if (empty($item['iblock_id'])) {
            return false;
        }

        $convertedFields = [];
        foreach ($item['fields'] as $field) {
            $method = $this->getConvertFieldMethod($item['iblock_id'], $field['name']);
            if (method_exists($this, $method)) {
                $convertedFields[$field['name']] = $this->$method($item['iblock_id'], $field);
            }
        }

        $convertedProperties = [];
        foreach ($item['properties'] as $prop) {
            $method = $this->getConvertPropertyMethod($item['iblock_id'], $prop['name']);
            if (method_exists($this, $method)) {
                $convertedProperties[$prop['name']] = $this->$method($item['iblock_id'], $prop);
            }
        }

        return [
            'iblock_id'  => $item['iblock_id'],
            'fields'     => $convertedFields,
            'properties' => $convertedProperties,
        ];
    }

    /**
     * @param $iblockId
     * @param $code
     *
     * @return string
     */
    protected function getConvertFieldMethod($iblockId, $code)
    {
        if (in_array($code, ['PREVIEW_PICTURE', 'DETAIL_PICTURE'])) {
            return 'convertFieldF';
        } elseif ($code == 'IBLOCK_SECTION') {
            return 'convertFieldIblockSection';
        } else {
            return 'convertFieldS';
        }
    }

    /**
     * @param $iblockId
     * @param $field
     *
     * @return mixed
     */
    protected function convertFieldS($iblockId, $field)
    {
        return $this->makeFieldValue($field['value'][0]);
    }

    /**
     * @param $iblockId
     * @param $field
     *
     * @throws HelperException
     * @return array
     */
    protected function convertFieldIblockSection($iblockId, $field)
    {
        $iblockExchange = $this->getHelperManager()->IblockExchange();

        $value = [];
        foreach ($field['value'] as $val) {
            $val['value'] = $iblockExchange->getSectionIdByUniqName($iblockId, $val['value']);
            $value[] = $this->makeFieldValue($val);
        }

        return $value;
    }

    /**
     * @param $iblockId
     * @param $field
     *
     * @return array|bool|null
     */
    protected function convertFieldF($iblockId, $field)
    {
        return $this->makeFileValue($field['value'][0]);
    }

    protected function getConvertPropertyMethod($iblockId, $code)
    {
        $iblockExchange = $this->getHelperManager()->IblockExchange();
        $type = $iblockExchange->getPropertyType($iblockId, $code);

        if (in_array($type, ['L', 'F', 'G', 'E'])) {
            return 'convertProperty' . ucfirst($type);
        } else {
            return 'convertPropertyS';
        }
    }

    protected function convertPropertyS($iblockId, $prop)
    {
        $iblockExchange = $this->getHelperManager()->IblockExchange();
        $isMultiple = $iblockExchange->isPropertyMultiple($iblockId, $prop['name']);
        $res = [];
        foreach ($prop['value'] as $val) {
            $res[] = $this->makePropertyValue($val);
        }

        return ($isMultiple) ? $res : $res[0];
    }

    protected function convertPropertyG($iblockId, $prop)
    {
        $iblockExchange = $this->getHelperManager()->IblockExchange();
        $isMultiple = $iblockExchange->isPropertyMultiple($iblockId, $prop['name']);
        $linkIblockId = $iblockExchange->getPropertyLinkIblockId($iblockId, $prop['name']);

        $res = [];
        if ($linkIblockId) {
            foreach ($prop['value'] as $val) {
                $val['value'] = $iblockExchange->getSectionIdByUniqName($linkIblockId, $val['value']);
                $res[] = $this->makePropertyValue($val);
            }
        }

        return ($isMultiple) ? $res : $res[0];
    }

    protected function convertPropertyE($iblockId, $prop)
    {
        $iblockExchange = $this->getHelperManager()->IblockExchange();
        $isMultiple = $iblockExchange->isPropertyMultiple($iblockId, $prop['name']);
        $linkIblockId = $iblockExchange->getPropertyLinkIblockId($iblockId, $prop['name']);

        $res = [];
        if ($linkIblockId) {
            foreach ($prop['value'] as $val) {
                $val['value'] = $iblockExchange->getElementIdByUniqName($linkIblockId, $val['value']);
                $res[] = $this->makePropertyValue($val);
            }
        }

        return ($isMultiple) ? $res : $res[0];
    }

    protected function convertPropertyF($iblockId, $prop)
    {
        $iblockExchange = $this->getHelperManager()->IblockExchange();
        $isMultiple = $iblockExchange->isPropertyMultiple($iblockId, $prop['name']);
        $res = [];
        foreach ($prop['value'] as $val) {
            $res[] = $this->makeFileValue($val);
        }
        return ($isMultiple) ? $res : $res[0];
    }

    protected function convertPropertyL($iblockId, $prop)
    {
        $iblockExchange = $this->getHelperManager()->IblockExchange();
        $isMultiple = $iblockExchange->isPropertyMultiple($iblockId, $prop['name']);
        $res = [];
        foreach ($prop['value'] as $val) {
            $val['value'] = $iblockExchange->getPropertyEnumIdByXmlId(
                $iblockId,
                $prop['name'],
                $val['value']
            );

            $res[] = $this->makePropertyValue($val);
        }
        return ($isMultiple) ? $res : $res[0];
    }

    protected function makeFieldValue($val)
    {
        return $val['value'];
    }

    protected function makePropertyValue($val)
    {
        $result = [
            'VALUE' => $val['value'],
        ];

        if (!empty($val['description'])) {
            $result['DESCRIPTION'] = $val['description'];
        }

        return $result;
    }
}
