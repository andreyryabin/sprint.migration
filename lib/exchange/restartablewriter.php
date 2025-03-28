<?php

namespace Sprint\Migration\Exchange;

use Closure;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Interfaces\RestartableInterface;
use Sprint\Migration\Out;

class RestartableWriter
{
    public function __construct(private readonly RestartableInterface $restartable)
    {
    }

    /**
     * @throws MigrationException
     * @throws RestartException
     */
    public function execute(
        string  $file,
        int     $limit,
        bool    $copyFiles,
        Closure $attributesFn,
        Closure $totalCountFn,
        Closure $recordsFn,
    ): void
    {
        $writer = new Writer($file);

        $writer->setCopyFiles($copyFiles);

        $this->restartable->restartOnce('step1', fn() => $writer->createFile($attributesFn()));

        $totalCount = $this->restartable->restartOnce('step2', fn() => $totalCountFn());

        $this->restartable->restartWhile('step3', fn(int $offset) => $this->write(
            $writer,
            $offset,
            $limit,
            $totalCount,
            $recordsFn
        ));

        $this->restartable->restartOnce('step4', fn() => $writer->closeFile());

    }

    /**
     * @throws MigrationException
     */
    private function write(
        Writer  $writer,
        int     $offset,
        int     $limit,
        int     $totalCount,
        Closure $recordsFn,
    ): int
    {
        $fetchedCount = $writer->appendTagsToFile($recordsFn($offset, $limit));

        $offset += $fetchedCount;

        Out::outProgress('Progress: ', $offset, $totalCount);

        return ($fetchedCount >= $limit) ? $offset : 0;
    }

}
