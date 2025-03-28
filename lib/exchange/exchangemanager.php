<?php

namespace Sprint\Migration\Exchange;

use Closure;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Interfaces\ReaderHelperInterface;
use Sprint\Migration\Interfaces\RestartableInterface;
use Sprint\Migration\Traits\HelperManagerTrait;

class ExchangeManager
{
    use HelperManagerTrait;

    private int $limit = 10;
    private string $file = '';
    private ?ReaderHelperInterface $exchangeHelper;


    public function __construct(
        private readonly RestartableInterface $restartable,
        private readonly string               $directory,
    )
    {
    }


    /**
     * @throws MigrationException
     * @throws RestartException
     */
    public function execute(Closure $userFn): void
    {
        $reader = (new RestartableReader($this->restartable));

        $recordFn = fn($attrs, $record) => $this->exchangeHelper->convertRecord($attrs, $record);

        $reader->execute(
            file: $this->file,
            limit: $this->limit,
            recordFn: $recordFn,
            userFn: $userFn
        );
    }


    public function IblockElementsImport(): ExchangeManager
    {
        return $this
            ->setExchangeResource('iblock_elements.xml')
            ->setExchangeHelper($this->getHelperManager()->IblockExchange());
    }

    public function HlblockElementsImport(): ExchangeManager
    {
        return $this
            ->setExchangeResource('hlblock_elements.xml')
            ->setExchangeHelper($this->getHelperManager()->HlblockExchange());
    }

    public function MedialibElementsImport(): ExchangeManager
    {
        return $this
            ->setExchangeResource('medialib_elements.xml')
            ->setExchangeHelper($this->getHelperManager()->MedialibExchange());
    }

    public function setExchangeResource(string $exchangeResource): ExchangeManager
    {
        return $this->setExchangeFile($this->directory . $exchangeResource);
    }

    public function setExchangeFile(string $filePath): ExchangeManager
    {
        $this->file = $filePath;
        return $this;
    }

    public function setLimit(int $limit): ExchangeManager
    {
        $this->limit = $limit;
        return $this;
    }

    public function setExchangeHelper(ReaderHelperInterface $exchangeHelper): ExchangeManager
    {
        $this->exchangeHelper = $exchangeHelper;
        return $this;
    }
}
