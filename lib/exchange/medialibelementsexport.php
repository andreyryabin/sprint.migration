<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\RestartException;
use XMLWriter;

class MedialibElementsExport extends AbstractExchange
{
    protected $collectionIds = [];
    protected $exportFields  = [
        'NAME',
        'DESCRIPTION',
        'KEYWORDS',
        'COLLECTION_ID',
        'SOURCE_ID',
    ];

    public function setCollectionIds($collectionIds = [])
    {
        $this->collectionIds = $collectionIds;
        return $this;
    }

    public function getCollectionIds()
    {
        return $this->collectionIds;
    }

    public function getExportFields()
    {
        return $this->exportFields;
    }

    /**
     * @throws RestartException
     */
    public function execute()
    {
        $medialibExchange = $this->getHelperManager()->MedialibExchange();

        $params = $this->exchangeEntity->getRestartParams();
        if (!isset($params['total'])) {
            $params['total'] = $medialibExchange->getElementsCount(
                $this->getCollectionIds()
            );
            $params['offset'] = 0;

            $this->createExchangeDir();

            $this->appendToExchangeFile('<?xml version="1.0" encoding="UTF-8"?>');
            $this->appendToExchangeFile('<items exchangeVersion="' . self::EXCHANGE_VERSION . '">');
        }

        if ($params['offset'] <= $params['total'] - 1) {
            $items = $medialibExchange->getElements(
                $this->getCollectionIds(),
                [
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
                        $writer->startElement('field');
                        if ($code == 'SOURCE_ID') {
                            $writer->writeAttribute('name', 'FILE');
                            $this->writeFieldFile($writer, $val);
                        } elseif ($code == 'COLLECTION_ID') {
                            $writer->writeAttribute('name', 'COLLECTION_PATH');
                            $this->writeFieldCollection($writer, $val);
                        } else {
                            $writer->writeAttribute('name', $code);
                            $this->writeValue($writer, $val);
                        }
                        $writer->endElement();
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

    private function writeFieldFile(XMLWriter $writer, $val)
    {
        $this->writeFile($writer, $val);
    }

    private function writeFieldCollection(XMLWriter $writer, $val)
    {
        $medialibExchange = $this->getHelperManager()->MedialibExchange();
        $this->writeValue(
            $writer,
            $medialibExchange->getCollectionPath($medialibExchange::TYPE_IMAGE, $val)
        );
    }
}
