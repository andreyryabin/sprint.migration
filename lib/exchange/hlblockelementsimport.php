<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\Base\ExchangeReader;
use Sprint\Migration\Helpers\HlblockExchangeHelper;

class HlblockElementsImport extends ExchangeReader
{
    protected int $hlblockId;

    public function setHlblockId(int $hlblockId): static
    {
        $this->hlblockId = $hlblockId;
        return $this;
    }

    /**
     * @throws HelperException
     */
    protected function convertRecord(array $record): array
    {
        $hblockExchange = new HlblockExchangeHelper();

        return $hblockExchange->convertRecord($this->hlblockId, $record);
    }
}
