<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\ExchangeWriter;
use Sprint\Migration\Exceptions\RestartException;
use XMLWriter;

class MedialibElementsExport extends ExchangeWriter
{
    protected $collectionIds = [];
    protected $exportFields = [
        'NAME',
        'DESCRIPTION',
        'KEYWORDS',
        'COLLECTION_ID',
        'SOURCE_ID',
    ];

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

            $this->createExchangeFile();
        }

        if ($params['offset'] <= $params['total'] - 1) {
            $items = $medialibExchange->getElements(
                $this->getCollectionIds(),
                [
                    'offset' => $params['offset'],
                    'limit' => $this->getLimit(),
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
                            $this->writeFile($writer, $val);
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

            $this->outProgress('Progress: ', $params['offset'], $params['total']);

            $this->exchangeEntity->setRestartParams($params);
            $this->exchangeEntity->restart();
        }

        $this->closeExchangeFile();

        unset($params['total']);
        unset($params['offset']);
        $this->exchangeEntity->setRestartParams($params);
    }

    public function getCollectionIds()
    {
        return $this->collectionIds;
    }

    public function setCollectionIds($collectionIds = [])
    {
        $this->collectionIds = $collectionIds;
        return $this;
    }

    public function getExportFields()
    {
        return $this->exportFields;
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
