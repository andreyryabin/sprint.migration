<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exchange\Base\ExchangeReader;
use Sprint\Migration\Helpers\MedialibExchangeHelper;

class MedialibElementsImport extends ExchangeReader
{

    /**
     * @throws HelperException
     */
    protected function convertRecord(array $record): array
    {
        $medialibExchangeHelper = new MedialibExchangeHelper();

        return $medialibExchangeHelper->convertRecord($record);
    }

}
