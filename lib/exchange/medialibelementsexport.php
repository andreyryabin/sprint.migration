<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\Base\ExchangeDto;
use Sprint\Migration\Exchange\Base\ExchangeWriter;
use Sprint\Migration\Helpers\MedialibExchangeHelper;

class MedialibElementsExport extends ExchangeWriter
{
    protected array $collectionIds = [];
    protected array $exportFields = [
        'NAME',
        'DESCRIPTION',
        'KEYWORDS',
        'COLLECTION_ID',
        'SOURCE_ID',
    ];

    public function setCollectionIds($collectionIds = []): static
    {
        $this->collectionIds = $collectionIds;
        return $this;
    }


    /**
     * @throws HelperException
     */
    protected function createRecords(int $offset, int $limit): ExchangeDto
    {
        $medialibExchangeHelper = new MedialibExchangeHelper();
        return $medialibExchangeHelper->createRecordsDto(
            $this->collectionIds,
            $offset,
            $limit,
            $this->exportFields
        );

    }

}
