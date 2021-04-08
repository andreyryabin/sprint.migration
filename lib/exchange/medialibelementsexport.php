<?php

namespace Sprint\Migration\Exchange;

use Exception;
use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RestartException;
use XMLWriter;

class MedialibElementsExport extends AbstractExchange
{
    /**
     * @throws RestartException
     * @throws HelperException
     * @throws Exception
     */
    public function execute()
    {
        $params = $this->exchangeEntity->getRestartParams();
        if (!isset($params['total'])) {
            $params['total'] = 0;
            $params['offset'] = 0;

            $this->createExchangeDir();

            $this->appendToExchangeFile('<?xml version="1.0" encoding="UTF-8"?>');
            $this->appendToExchangeFile('<items exchangeVersion="' . self::EXCHANGE_VERSION . '">');
        }

        if ($params['offset'] <= $params['total'] - 1) {
            $items = [];

            foreach ($items as $item) {
                $writer = new XMLWriter();
                $writer->openMemory();
                $writer->startElement('item');
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
}
