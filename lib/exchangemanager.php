<?php

namespace Sprint\Migration;

use Sprint\Migration\Exchange\IblockElementsExport;
use Sprint\Migration\Exchange\IblockElementsImport;


class ExchangeManager
{
    protected $exchangeEntity;

    public function __construct(ExchangeEntity $exchangeEntity)
    {
        $this->exchangeEntity = $exchangeEntity;
    }

    /**
     * @throws Exceptions\ExchangeException
     * @return IblockElementsExport
     */
    public function IblockElementsExport()
    {
        return new IblockElementsExport($this->exchangeEntity);
    }

    /**
     * @throws Exceptions\ExchangeException
     * @return IblockElementsImport
     */
    public function IblockElementsImport()
    {
        return new IblockElementsImport($this->exchangeEntity);
    }
}
