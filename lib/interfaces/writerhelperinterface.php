<?php

namespace Sprint\Migration\Interfaces;

use Sprint\Migration\Exchange\WriterTag;

interface WriterHelperInterface
{

    public function getWriterAttributes(...$vars): array;

    public function getWriterRecordsCount(...$vars): int;

    public function getWriterRecordsTag(int $offset, int $limit, ...$vars): WriterTag;
}
