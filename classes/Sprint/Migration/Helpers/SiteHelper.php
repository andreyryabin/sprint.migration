<?php

namespace Sprint\Migration\Helpers;
use Sprint\Migration\Helper;

class SiteHelper extends Helper
{

    /**
     * @return array
     * @throws HelperException
     */
    public function getDefaultSiteIds(){
        $by = 'def';
        $order = 'desc';

        $lids = array();
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CSite::GetList($by, $order, array('ACTIVE' => 'Y', 'DEF' => 'Y'));
        while ($aItem = $dbRes->Fetch()){
            $lids[] = $aItem['LID'];
        }

        if (!empty($lids)){
            return $lids;
        }

        $this->throwException(__METHOD__, 'sites not found');
    }
}