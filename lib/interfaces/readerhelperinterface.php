<?php

namespace Sprint\Migration\Interfaces;

interface ReaderHelperInterface
{

    public function convertReaderRecords(array $attributes, array $records): array;


}
