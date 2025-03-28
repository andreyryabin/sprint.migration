<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Interfaces\RestartableInterface;
use Sprint\Migration\Traits\HelperManagerTrait;

class ExchangeManager
{
    use HelperManagerTrait {
        getHelperManager as private;
    }

    public function __construct(
        private readonly RestartableInterface $restartable,
        private readonly string               $directory,
    )
    {
    }

    public function IblockElementsImport(): RestartableReader
    {
        return (new RestartableReader(
            $this->restartable,
            $this->getHelperManager()->IblockExchange(),
            $this->directory

        ))->setExchangeResource('iblock_elements.xml');
    }

    public function HlblockElementsImport(): RestartableReader
    {
        return (new RestartableReader(
            $this->restartable,
            $this->getHelperManager()->HlblockExchange(),
            $this->directory

        ))->setExchangeResource('hlblock_elements.xml');
    }

    public function MedialibElementsImport(): RestartableReader
    {
        return (new RestartableReader(
            $this->restartable,
            $this->getHelperManager()->MedialibExchange(),
            $this->directory

        ))->setExchangeResource('medialib_elements.xml');
    }
}
