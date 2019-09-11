<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\Helpers\HlblockExchangeHelper;
use Sprint\Migration\ExchangeEntity;
use XMLWriter;

/**
 * @property  HlblockExchangeHelper $exchangeHelper
 */
class HlblockElementsExport extends AbstractExchange
{
    protected $hlblockId;

    protected $exportFields = [];

    public function __construct(ExchangeEntity $exchangeEntity)
    {
        parent::__construct($exchangeEntity, new HlblockExchangeHelper());
    }

    public function setHlblockId($hlblockId)
    {
        $this->hlblockId = $hlblockId;
        return $this;
    }

    /**
     * @throws RestartException
     * @throws HelperException
     */
    public function execute()
    {
        $params = $this->exchangeEntity->getRestartParams();
        if (!isset($params['total'])) {
            $params['total'] = $this->exchangeHelper->getElementsCount($this->hlblockId);
            $params['offset'] = 0;

            $this->createExchangeDir();

            $hlblockUid = $this->exchangeHelper->getHlblockUid($this->hlblockId);

            $this->appendToExchangeFile('<?xml version="1.0" encoding="UTF-8"?>');
            $this->appendToExchangeFile('<items hlblockUid="' . $hlblockUid . '">');
        }

        if ($params['offset'] <= $params['total'] - 1) {
            $items = $this->exchangeHelper->getElements(
                $this->hlblockId,
                $params['offset'],
                $this->getLimit()
            );

            foreach ($items as $item) {
                $writer = new XMLWriter();
                $writer->openMemory();
                $writer->startElement('item');

                foreach ($item as $code => $val) {
                    if (in_array($code, $this->getExportFields())) {
                        $method = $this->getWriteFieldMethod($code);
                        if (method_exists($this, $method)) {
                            $writer->startElement('field');
                            $writer->writeAttribute('name', $code);
                            $this->$method($writer, [
                                'FIELD_NAME' => $code,
                                'VALUE' => $val,
                            ]);
                            $writer->endElement();
                        }
                    }
                }

                //item
                $writer->endElement();
                $this->appendToExchangeFile($writer->flush());
                $params['offset']++;
            }

            $this->outProgress('', $params['offset'], $params['total']);

            $this->exchangeEntity->setRestartParams($params);
            $this->restart();
        }

        $this->appendToExchangeFile('</items>');
        unset($params['total']);
        unset($params['offset']);
        $this->exchangeEntity->setRestartParams($params);
    }

    /**
     * @param $code
     * @throws HelperException
     * @return string
     */
    protected function getWriteFieldMethod($code)
    {
        $type = $this->exchangeHelper->getFieldType($this->hlblockId, $code);

        if (in_array($type, ['enumeration', 'file'])) {
            return 'writeField' . ucfirst($type);
        } else {
            return 'writeFieldString';
        }
    }

    protected function writeFieldString(XMLWriter $writer, $field)
    {
        $this->writeValue($writer, $field['VALUE']);
    }

    protected function writeFieldFile(XMLWriter $writer, $field)
    {
        $this->writeFile($writer, $field['VALUE']);
    }

    /**
     * @param XMLWriter $writer
     * @param $field
     * @throws HelperException
     */
    protected function writeFieldEnumeration(XMLWriter $writer, $field)
    {
        $idValues = is_array($field['VALUE']) ? $field['VALUE'] : [$field['VALUE']];
        $xmlValues = [];
        foreach ($idValues as $id) {
            $xmlId = $this->exchangeHelper->getFieldEnumXmlIdById(
                $this->hlblockId,
                $field['FIELD_NAME'],
                $id
            );
            if ($xmlId) {
                $xmlValues[] = $xmlId;
            }
        }

        $this->writeValue($writer, $xmlValues);
    }

    /**
     * @param array $exportFields
     * @return $this
     */
    public function setExportFields(array $exportFields)
    {
        $this->exportFields = $exportFields;
        return $this;
    }

    protected function getExportFields()
    {
        return $this->exportFields;
    }
}
