<?php

namespace Sprint\Migration\Exchange;

use Closure;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Interfaces\RestartableInterface;
use Sprint\Migration\Out;

class RestartableReader
{

    public function __construct(private readonly RestartableInterface $restartable)
    {

    }

    /**
     * @throws RestartException
     * @throws MigrationException
     */
    public function execute(
        string  $file,
        int     $limit,
        Closure $recordFn,
        Closure $userFn
    ): void
    {
        $reader = new Reader($file);

        $attrs = $this->restartable->restartOnce('step1', fn() => $reader->getAttributes());

        $totalCount = $this->restartable->restartOnce('step2', fn() => $reader->getRecordsCount());

        $this->restartable->restartWhile('step3', fn(int $offset) => $this->read(
            $reader,
            $attrs,
            $offset,
            $limit,
            $totalCount,
            $recordFn,
            $userFn,
        ));
    }

    private function read(
        Reader  $reader,
        array   $attrs,
        int     $offset,
        int     $limit,
        int     $totalCount,
        Closure $recordsFn,
        Closure $userfunc
    ): int
    {
        $records = array_map(
            fn($record) => $recordsFn($attrs, $record),
            $reader->readRecords($offset, $limit)
        );

        array_map($userfunc, $records);

        $readCount = count($records);

        $offset += $readCount;

        Out::outProgress('Progress: ', $offset, $totalCount);

        return ($readCount >= $limit) ? $offset : 0;
    }
}
