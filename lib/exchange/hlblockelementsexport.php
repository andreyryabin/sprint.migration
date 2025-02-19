<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\Base\ExchangeDto;
use Sprint\Migration\Exchange\Base\ExchangeWriter;
use Sprint\Migration\Helpers\HlblockExchangeHelper;


class HlblockElementsExport extends ExchangeWriter
{
    protected $hlblockId;
    protected array $exportFields = [];

    public function setHlblockId($hlblockId): static
    {
        $this->hlblockId = $hlblockId;
        return $this;
    }


    public function setExportFields(array $exportFields): static
    {
        $this->exportFields = $exportFields;
        return $this;
    }

    /**
     * @throws HelperException
     */
    protected function createRecords(int $offset, int $limit): ExchangeDto
    {
        $hlblockExchangeHelper = new HlblockExchangeHelper();

        return $hlblockExchangeHelper->createRecordsDto(
            $this->hlblockId,
            $offset,
            $limit,
            $this->exportFields
        );
    }
}
