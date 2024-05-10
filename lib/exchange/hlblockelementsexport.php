<?php

namespace Sprint\Migration\Exchange;

use Exception;
use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RestartException;
use XMLWriter;

class HlblockElementsExport extends AbstractExchange
{
    protected $hlblockId;
    protected $updateMode;
    protected $exportFields = [];
    const UPDATE_MODE_NOT    = 'not';
    const UPDATE_MODE_XML_ID = 'xml_id';

    public function setHlblockId($hlblockId)
    {
        $this->hlblockId = $hlblockId;
        return $this;
    }

    public function setUpdateMode(string $updateMode)
    {
        $this->updateMode = $updateMode;
        return $this;
    }

    /**
     * @throws RestartException
     * @throws HelperException
     * @throws Exception
     */
    public function execute()
    {
        $hblockExchange = $this->getHelperManager()->HlblockExchange();

        $params = $this->exchangeEntity->getRestartParams();
        if (!isset($params['total'])) {
            $hlblockUid = $hblockExchange->getHlblockUid($this->hlblockId);

            $params['total'] = $hblockExchange->getElementsCount($this->hlblockId);
            $params['offset'] = 0;

            $this->createExchangeDir();

            $this->appendToExchangeFile('<?xml version="1.0" encoding="UTF-8"?>');
            $this->appendToExchangeFile('<items hlblockUid="' . $hlblockUid . '" exchangeVersion="' . self::EXCHANGE_VERSION . '">');
        }

        if ($params['offset'] <= $params['total'] - 1) {
            $items = $hblockExchange->getElements(
                $this->hlblockId,
                [
                    'order'  => ['ID' => 'ASC'],
                    'offset' => $params['offset'],
                    'limit'  => $this->getLimit(),
                ]
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
                            $this->$method(
                                $writer, [
                                    'FIELD_NAME' => $code,
                                    'VALUE'      => $val,
                                ]
                            );
                            $writer->endElement();
                        }
                    }
                }

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
     *
     * @throws HelperException
     * @return string
     */
    protected function getWriteFieldMethod($code)
    {
        $hblockExchange = $this->getHelperManager()->HlblockExchange();

        $type = $hblockExchange->getFieldType($this->hlblockId, $code);

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

    /**
     * @param XMLWriter $writer
     * @param           $field
     *
     * @throws Exception
     */
    protected function writeFieldFile(XMLWriter $writer, $field)
    {
        $this->writeFile($writer, $field['VALUE']);
    }

    /**
     * @param XMLWriter $writer
     * @param           $field
     *
     * @throws HelperException
     */
    protected function writeFieldEnumeration(XMLWriter $writer, $field)
    {
        $hblockExchange = $this->getHelperManager()->HlblockExchange();

        $idValues = is_array($field['VALUE']) ? $field['VALUE'] : [$field['VALUE']];
        $xmlValues = [];
        foreach ($idValues as $id) {
            $xmlId = $hblockExchange->getFieldEnumXmlIdById(
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
     *
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
