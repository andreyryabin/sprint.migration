<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exchange\HlblockElementsImport;
use Sprint\Migration\Exchange\IblockElementsImport;
use Sprint\Migration\Exchange\MedialibElementsImport;

class ExchangeManager
{
    protected $exchangeEntity;

    public function __construct(ExchangeEntity $exchangeEntity)
    {
        $this->exchangeEntity = $exchangeEntity;
    }

    /**
     * @throws MigrationException
     */
    public function IblockElementsImport(): IblockElementsImport
    {
        return new IblockElementsImport($this->exchangeEntity);
    }

    /**
     * @throws MigrationException
     */
    public function HlblockElementsImport(): HlblockElementsImport
    {
        return new HlblockElementsImport($this->exchangeEntity);
    }

    /**
     * @throws MigrationException
     */
    public function MedialibElementsImport(): MedialibElementsImport
    {
        return new MedialibElementsImport($this->exchangeEntity);
    }
}
