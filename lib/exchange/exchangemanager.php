<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Traits\HelperManagerTrait;
use Sprint\Migration\Version;

class ExchangeManager
{
    use HelperManagerTrait;

    protected Version $restartable;

    public function __construct(Version $restartable)
    {
        $this->restartable = $restartable;

    }

    protected function getExchangeFile(string $fileName): string
    {
        return $this->restartable->getVersionConfig()->getVersionExchangeFile(
            $this->restartable->getVersionName(),
            $fileName
        );
    }

    /**
     * @throws MigrationException
     */
    public function IblockElementsImport(): ExchangeReader
    {
        $exhelper = $this->getHelperManager()->IblockExchange();

        return (new ExchangeReader(
            $this->restartable,
            fn($attrs, $record) => $exhelper->convertRecord($attrs, $record)
        ))->setExchangeFile($this->getExchangeFile('iblock_elements.xml'));
    }

    /**
     * @throws MigrationException
     */
    public function HlblockElementsImport(): ExchangeReader
    {
        $exhelper = $this->getHelperManager()->HlblockExchange();

        return (new ExchangeReader(
            $this->restartable,
            fn($attrs, $record) => $exhelper->convertRecord($attrs, $record)
        ))->setExchangeFile($this->getExchangeFile('hlblock_elements.xml'));
    }

    /**
     * @throws MigrationException
     */
    public function MedialibElementsImport(): ExchangeReader
    {
        $exhelper = $this->getHelperManager()->MedialibExchange();

        return (new ExchangeReader(
            $this->restartable,
            fn($attrs, $record) => $exhelper->convertRecord($attrs, $record)
        ))->setExchangeFile($this->getExchangeFile('medialib_elements.xml'));
    }
}
