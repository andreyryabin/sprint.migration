<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\Base\ExchangeWriter;

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
     * @throws HelperException
     * @throws \Exception
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

            $dto = $medialibExchange->getElementsExchangeDto(
                $this->getCollectionIds(),
                [
                    'offset' => $params['offset'],
                    'limit' => $this->getLimit(),
                ],
                $this->getExportFields()
            );

            $this->appendDtoToExchangeFile($dto);

            $params['offset'] += $dto->countChilds();

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
}
