<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exchange\Helpers\BlogExchangeHelper;
use Sprint\Migration\Exchange\Helpers\HlblockExchangeHelper;
use Sprint\Migration\Exchange\Helpers\IblockExchangeHelper;
use Sprint\Migration\Exchange\Helpers\MedialibExchangeHelper;
use Sprint\Migration\HelperRegistry;

/**
 * @method IblockExchangeHelper     IblockExchange()
 * @method HlblockExchangeHelper    HlblockExchange()
 * @method MedialibExchangeHelper   MedialibExchange()
 * @method BlogExchangeHelper       BlogExchange()
 */
class ExchangeHelpers extends HelperRegistry
{
    protected function getClassByCalledName(string $name): string
    {
        return '\\Sprint\\Migration\\Exchange\\Helpers\\' . $name . 'Helper';
    }
}
