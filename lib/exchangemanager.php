<?php

namespace Sprint\Migration;

use Sprint\Migration\Exchange\IblockElementsExport;
use Sprint\Migration\Exchange\IblockElementsImport;


class ExchangeManager
{
    protected $restartable;

    public function __construct(ExchangeInterface $restartable)
    {
        $this->restartable = $restartable;
    }

    /**
     * @throws Exceptions\ExchangeException
     * @return IblockElementsExport
     */
    public function IblockElementsExport()
    {
        return new IblockElementsExport($this->restartable);
    }

    /**
     * @throws Exceptions\ExchangeException
     * @return IblockElementsImport
     */
    public function IblockElementsImport()
    {
        return new IblockElementsImport($this->restartable);
    }
}
