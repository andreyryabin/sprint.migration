<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exchange\Base\ExchangeReader;
use Sprint\Migration\Exchange\HlblockElementsImport;
use Sprint\Migration\Exchange\IblockElementsImport;
use Sprint\Migration\Exchange\MedialibElementsImport;

class ExchangeManager
{
    protected Version $versionEntity;

    public function __construct(Version $versionEntity)
    {
        $this->versionEntity = $versionEntity;

    }

    protected function getExchangeFile(string $name): string
    {
        $dir = $this->versionEntity->getVersionConfig()->getVal('exchange_dir');
        return $dir . '/' . $this->versionEntity->getVersionName() . '_files/' . $name;
    }

    /**
     * @throws MigrationException
     */
    public function IblockElementsImport(): ExchangeReader
    {
        return (new ExchangeReader($this->versionEntity))
            ->setExchangeFile($this->getExchangeFile('iblock_elements.xml'));
    }

    /**
     * @throws MigrationException
     */
    public function HlblockElementsImport(): ExchangeReader
    {
        return (new ExchangeReader($this->versionEntity))
            ->setExchangeFile($this->getExchangeFile('hlblock_elements.xml'));
    }

    /**
     * @throws MigrationException
     */
    public function MedialibElementsImport(): ExchangeReader
    {
        return (new ExchangeReader($this->versionEntity))
            ->setExchangeFile($this->getExchangeFile('medialib_elements.xml'));
    }
}
