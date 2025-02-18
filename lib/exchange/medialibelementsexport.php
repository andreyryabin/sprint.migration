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

    public function getCollectionIds(): array
    {
        return $this->collectionIds;
    }

    public function setCollectionIds($collectionIds = []): static
    {
        $this->collectionIds = $collectionIds;
        return $this;
    }

    public function getExportFields(): array
    {
        return $this->exportFields;
    }

    /**
     * @throws HelperException
     */
    protected function getRecordsDto(int $offset, int $limit): ExchangeDto
    {
        $medialibExchangeHelper = new MedialibExchangeHelper();

        return $medialibExchangeHelper->getElementsExchangeDto(
            $this->getCollectionIds(),
            [
                'offset' => $offset,
                'limit' => $limit,
            ],
            $this->getExportFields()
        );
    }
}
