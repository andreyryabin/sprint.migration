<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\Base\ExchangeDto;
use Sprint\Migration\Exchange\Base\ExchangeWriter;
use Sprint\Migration\Helpers\IblockExchangeHelper;

class IblockElementsExport extends ExchangeWriter
{
    protected $iblockId;
    protected array $exportFilter = [];
    protected array $exportFields = [];
    protected array $exportProperties = [];

    public function setIblockId($iblockId)
    {
        $this->iblockId = $iblockId;
        return $this;
    }

    public function setExportFilter(array $exportFilter): static
    {
        $this->exportFilter = $exportFilter;
        return $this;
    }


    public function setExportFields(array $exportFields): static
    {
        $this->exportFields = $exportFields;
        return $this;
    }

    public function setExportProperties(array $exportProperties): static
    {
        $this->exportProperties = $exportProperties;
        return $this;
    }

    /**
     * @throws HelperException
     */
    protected function createRecords(int $offset, int $limit): ExchangeDto
    {
        $iblockExchangeHelper = new IblockExchangeHelper();

        return $iblockExchangeHelper->createRecordsDto(
            $this->iblockId,
            $offset,
            $limit,
            $this->exportFilter,
            $this->exportFields,
            $this->exportProperties
        );

    }
}
