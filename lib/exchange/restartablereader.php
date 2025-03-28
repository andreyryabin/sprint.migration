<?php

namespace Sprint\Migration\Exchange;

use Closure;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Interfaces\ReaderHelperInterface;
use Sprint\Migration\Interfaces\RestartableInterface;
use Sprint\Migration\Out;

class RestartableReader
{
    private int $limit = 10;
    private string $file = '';
    private array $attributes = [];
    private int $totalCount = 0;
    private ?Reader $reader;

    public function __construct(
        private readonly RestartableInterface  $restartable,
        private readonly ReaderHelperInterface $helper,
        private readonly string                $directory,
    )
    {
    }

    public function setExchangeResource(string $exchangeResource): RestartableReader
    {
        return $this->setExchangeFile($this->directory . $exchangeResource);
    }

    public function setExchangeFile(string $filePath): RestartableReader
    {
        $this->file = $filePath;
        return $this;
    }

    public function setLimit(int $limit): RestartableReader
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @throws MigrationException
     * @throws RestartException
     */
    public function execute(Closure $userFn): void
    {
        $this->reader = new Reader($this->file);

        $this->attributes = $this->restartable->restartOnce('step1', fn() => $this->reader->getAttributes());

        $this->totalCount = $this->restartable->restartOnce('step2', fn() => $this->reader->getRecordsCount());

        $this->restartable->restartWhile('step3', fn(int $offset) => $this->read($offset, $userFn));
    }

    private function read(int $offset, Closure $userfunc): int
    {
        $records = $this->helper->convertReaderRecords(
            $this->attributes,
            $this->reader->readRecords($offset, $this->limit)
        );

        array_map($userfunc, $records);

        $readCount = count($records);

        $offset += $readCount;

        Out::outProgress('Progress: ', $offset, $this->totalCount);

        return ($readCount >= $this->limit) ? $offset : 0;
    }


}
