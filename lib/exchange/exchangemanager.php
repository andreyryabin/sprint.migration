<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Traits\HelperManagerTrait;
use Sprint\Migration\Version;

class ExchangeManager
{
    use HelperManagerTrait;

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
        $exhelper = $this->getHelperManager()->IblockExchange();

        return (new ExchangeReader($this->versionEntity))
            ->setHelperConverter(
                fn($attrs, $record) => $exhelper->convertRecord($attrs, $record)
            )
            ->setExchangeFile($this->getExchangeFile('iblock_elements.xml'));
    }

    /**
     * @throws MigrationException
     */
    public function HlblockElementsImport(): ExchangeReader
    {
        $exhelper = $this->getHelperManager()->HlblockExchange();

        return (new ExchangeReader($this->versionEntity))
            ->setHelperConverter(
                fn($attrs, $record) => $exhelper->convertRecord($attrs, $record)
            )->setExchangeFile($this->getExchangeFile('hlblock_elements.xml'));
    }

    /**
     * @throws MigrationException
     */
    public function MedialibElementsImport(): ExchangeReader
    {
        $exhelper = $this->getHelperManager()->MedialibExchange();

        return (new ExchangeReader($this->versionEntity))
            ->setHelperConverter(
                fn($attrs, $record) => $exhelper->convertRecord($attrs, $record)
            )->setExchangeFile($this->getExchangeFile('medialib_elements.xml'));
    }
}
