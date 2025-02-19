<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\Base\ExchangeReader;
use Sprint\Migration\Helpers\IblockExchangeHelper;

class IblockElementsImport extends ExchangeReader
{
    protected int $iblockId;

    public function setIblockId(int $iblockId): static
    {
        $this->iblockId = $iblockId;
        return $this;
    }

    /**
     * @throws HelperException
     */
    protected function convertRecord($record): array
    {
        $iblockExchangeHelper = new IblockExchangeHelper();

        return $iblockExchangeHelper->convertRecord($this->iblockId, $record);
    }
}
