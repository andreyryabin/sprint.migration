<?php

namespace Sprint\Migration\Exchange;

use Closure;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Interfaces\RestartableInterface;

class RestartableWriter
{
    private int     $limit      = 20;
    private string  $file       = '';
    private bool    $copyFiles  = true;
    private int     $totalCount = 0;
    private ?Writer $writer;

    public function __construct(
        private readonly RestartableInterface $restartable,
        private readonly string $directory,
    ) {
    }

    public function setExchangeResource(string $exchangeResource): RestartableWriter
    {
        return $this->setExchangeFile($this->directory . $exchangeResource);
    }

    public function setExchangeFile(string $filePath): RestartableWriter
    {
        $this->file = $filePath;
        return $this;
    }

    public function setLimit(int $limit): RestartableWriter
    {
        $this->limit = $limit;
        return $this;
    }

    public function setCopyFiles(bool $copyFiles): RestartableWriter
    {
        $this->copyFiles = $copyFiles;
        return $this;
    }

    /**
     * @throws MigrationException
     * @throws RestartException
     */
    public function execute(
        Closure $attributesFn,
        Closure $totalCountFn,
        Closure $recordsFn,
        Closure $progressFn,
    ): void {
        $this->writer = new Writer($this->file);

        $this->writer->setCopyFiles($this->copyFiles);

        $this->restartable->restartOnce('step1', fn() => $this->writer->createFile($attributesFn()));

        $this->totalCount = $this->restartable->restartOnce('step2', fn() => $this->start($totalCountFn, $progressFn));

        $this->restartable->restartWhile('step3', fn(int $offset) => $this->write($offset, $recordsFn, $progressFn));

        $this->restartable->restartOnce('step4', fn() => $this->writer->closeFile());
    }
    private function start(Closure $totalCountFn, Closure $progressFn): int
    {
        $totalCount = $totalCountFn();

        $progressFn(0, $totalCount);

        return $totalCount;
    }
    /**
     * @throws MigrationException
     */
    private function write(int $offset, Closure $recordsFn, Closure $progressFn): int
    {
        /** @var WriterTag $tags */
        $tags = $recordsFn($offset, $this->limit);

        $fetchedCount = $this->writer->appendTagsToFile($tags);

        $offset += $fetchedCount;

        $progressFn($offset, $this->totalCount);

        return ($fetchedCount >= $this->limit) ? $offset : 0;
    }
}
