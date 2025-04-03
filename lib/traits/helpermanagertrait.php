<?php

namespace Sprint\Migration\Traits;

use Sprint\Migration\HelperManager;

trait HelperManagerTrait
{
    public function getHelperManager(): HelperManager
    {
        return HelperManager::getInstance();
    }
}
