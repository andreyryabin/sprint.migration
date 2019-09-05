<?php

namespace Sprint\Migration\Exchange\Helpers;

use Sprint\Migration\HelperManager;

class ExchangeHelper
{

    public function isEnabled()
    {
        return true;
    }

    protected function getHelperManager()
    {
        return HelperManager::getInstance();
    }
}