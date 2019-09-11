<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\Helpers\IblockExchangeHelper;
use Sprint\Migration\ExchangeEntity;
use XMLReader;

/**
 * @property  IblockExchangeHelper $exchangeHelper
 */
class IblockElementsImport extends AbstractExchange
{
    protected $converter;

    /**
     * IblockElementsImport constructor.
     * @param ExchangeEntity $exchangeEntity
     * @throws ExchangeException
     */
    public function __construct(ExchangeEntity $exchangeEntity)
    {
        parent::__construct($exchangeEntity, new IblockExchangeHelper());
    }

    /**
     * @param callable $converter
     * @throws ExchangeException
     * @throws RestartException
     */
    public function execute(callable $converter)
    {
        $this->converter = $converter;

        $params = $this->exchangeEntity->getRestartParams();

        if (!isset($params['total'])) {
            $this->exchangeEntity->exitIf(
                !is_callable($this->converter), 'converter not callable'
            );

            $this->exchangeEntity->exitIf(
                !is_file($this->file), 'exchange file not found'
            );

            $reader = new XMLReader();
            $reader->open($this->getExchangeFile());
            $params['total'] = 0;
            $params['offset'] = 0;
            $params['iblock_id'] = 0;

            while ($reader->read()) {
                if ($this->isOpenTag($reader, 'items')) {
                    $params['iblock_id'] = $this->exchangeHelper->getIblockIdByUid(
                        $reader->getAttribute('iblockUid')
                    );
                }

                if ($this->isOpenTag($reader, 'item')) {
                    $params['total']++;
                }
            }

            $reader->close();

            $this->exchangeEntity->exitIfEmpty(
                $params['iblock_id'], 'iblockId not found'
            );

        }

        $reader = new XMLReader();
        $reader->open($this->getExchangeFile());
        $index = 0;

        while ($reader->read()) {
            if ($this->isOpenTag($reader, 'item')) {

                $collect = (
                    $index >= $params['offset'] &&
                    $index < $params['offset'] + $this->getLimit()
                );

                $finish = ($index >= $params['total'] - 1);
                $restart = ($index >= $params['offset'] + $this->getLimit());

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
     * @param $iblockId
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

            $convertedItem = $this->convertItem([
                'iblock_id' => $iblockId,
                'fields' => $fields,
                'properties' => $props,
            ]);

            if ($convertedItem) {
                call_user_func($this->converter, $convertedItem);
            }
        }
    }

    /**
     * @param $item
     * @return array|bool
     */
    protected function convertItem($item)
    {
        if (empty($item['iblock_id'])) {
            return false;
        }
        if (empty($item['fields'])) {
            return false;
        }

        $convertedFields = [];
        foreach ($item['fields'] as $field) {
            $method = $this->getConvertFieldMethod($field['name']);
            if (method_exists($this, $method)) {
                $convertedFields[$field['name']] = $this->$method($field);
            }
        }

        if (empty($convertedFields)) {
            return false;
        }

        $convertedProperties = [];
        foreach ($item['properties'] as $prop) {
            $method = $this->getConvertPropertyMethod($item['iblock_id'], $prop['name']);
            if (method_exists($this, $method)) {
                $convertedProperties[$prop['name']] = $this->$method($item['iblock_id'], $prop);
            }
        }

        return [
            'iblock_id' => $item['iblock_id'],
            'fields' => $convertedFields,
            'properties' => $convertedProperties,
        ];
    }

    protected function getConvertFieldMethod($code)
    {
        if (in_array($code, ['PREVIEW_PICTURE', 'DETAIL_PICTURE'])) {
            return 'convertFieldF';
        } else {
            return 'convertFieldS';
        }
    }

    protected function convertFieldS($field)
    {
        return $field['value'][0]['value'];
    }

    protected function convertFieldF($field)
    {
        return $this->makeFile($field['value'][0]);
    }

    protected function getConvertPropertyMethod($iblockId, $code)
    {
        $type = $this->exchangeHelper->getPropertyType($iblockId, $code);

        if (in_array($type, ['L', 'F'])) {
            return 'convertProperty' . ucfirst($type);
        } else {
            return 'convertPropertyS';
        }
    }

    protected function convertPropertyS($iblockId, $prop)
    {
        if ($this->exchangeHelper->isPropertyMultiple($iblockId, $prop['name'])) {
            $res = [];
            foreach ($prop['value'] as $val) {
                $res[] = $val['value'];
            }
            return $res;
        } else {
            return $prop['value'][0]['value'];
        }
    }

    protected function convertPropertyF($iblockId, $prop)
    {
        if ($this->exchangeHelper->isPropertyMultiple($iblockId, $prop['name'])) {
            $res = [];
            foreach ($prop['value'] as $val) {
                $res[] = $this->makeFile($val);
            }
            return $res;
        } else {
            return $this->makeFile($prop['value'][0]);
        }
    }

    protected function convertPropertyL($iblockId, $prop)
    {
        if ($this->exchangeHelper->isPropertyMultiple($iblockId, $prop['name'])) {
            $res = [];
            foreach ($prop['value'] as $val) {
                $res[] = $this->exchangeHelper->getPropertyEnumIdByXmlId(
                    $iblockId,
                    $prop['name'],
                    $val['value']
                );
            }
            return $res;
        } else {
            return $this->exchangeHelper->getPropertyEnumIdByXmlId(
                $iblockId,
                $prop['name'],
                $prop['value'][0]['value']
            );
        }
    }
}