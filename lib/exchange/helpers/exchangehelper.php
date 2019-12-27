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

    protected function getPageCountFromElementsCount($total, $limit)
    {
        return (int)ceil($total / $limit);
    }

    protected function getPageNumberFromOffset($offset, $limit)
    {
        return (int)floor($offset / $limit) + 1;
    }

    protected function getOffsetFromPageNumber($pageNumber, $limit)
    {
        $pageNumber = (int)$pageNumber;
        $pageNumber = ($pageNumber < 1) ? 1 : $pageNumber;

        return ($pageNumber - 1) * $limit;
    }
}
