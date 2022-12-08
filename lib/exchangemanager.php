<?php

namespace Sprint\Migration;

use Sprint\Migration\Exchange\HlblockElementsExport;
use Sprint\Migration\Exchange\HlblockElementsImport;
use Sprint\Migration\Exchange\IblockElementsExport;
use Sprint\Migration\Exchange\IblockElementsImport;
use Sprint\Migration\Exchange\MedialibElementsExport;
use Sprint\Migration\Exchange\MedialibElementsImport;

class ExchangeManager
{
    protected $exchangeEntity;

    public function __construct(ExchangeEntity $exchangeEntity)
    {
        $this->exchangeEntity = $exchangeEntity;
    }

    /**
     * @throws Exceptions\MigrationException
     * @return IblockElementsExport
     */
    public function IblockElementsExport()
    {
        return new IblockElementsExport($this->exchangeEntity);
    }

    /**
     * @throws Exceptions\MigrationException
     * @return IblockElementsImport
     */
    public function IblockElementsImport()
    {
        return new IblockElementsImport($this->exchangeEntity);
    }

    /**
     * @throws Exceptions\MigrationException
     * @return HlblockElementsImport
     */
    public function HlblockElementsImport()
    {
        return new HlblockElementsImport($this->exchangeEntity);
    }

    /**
     * @throws Exceptions\MigrationException
     * @return HlblockElementsExport
     */
    public function HlblockElementsExport()
    {
        return new HlblockElementsExport($this->exchangeEntity);
    }

    /**
     * @throws Exceptions\MigrationException
     * @return MedialibElementsExport
     */
    public function MedialibElementsExport()
    {
        return new MedialibElementsExport($this->exchangeEntity);
    }

    /**
     * @throws Exceptions\MigrationException
     * @return MedialibElementsImport
     */
    public function MedialibElementsImport()
    {
        return new MedialibElementsImport($this->exchangeEntity);
    }
}
