<?php

namespace Sprint\Migration\Interfaces;

interface ReaderHelperInterface
{

    public function convertRecord(array $attrs, array $record): array;


}
