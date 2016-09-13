<?php

namespace Sprint\Migration\Helpers;
use Sprint\Migration\Helper;

class SiteHelper extends Helper
{

    public function getDefaultSiteIdIfExists(){
        $by = 'def';
        $order = 'desc';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $aItem = \CSite::GetList($by, $order, array('ACTIVE' => 'Y'))->Fetch();

        if ($aItem){
            return $aItem['LID'];
        }

        $this->throwException(__METHOD__, 'Default site not found');
    }

    public function getSites($filter = array()) {
        $by = 'def';
        $order = 'desc';

        $sids = array();
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CSite::GetList($by, $order, $filter);
        while ($aItem = $dbRes->Fetch()) {
            $sids[] = $aItem;
        }

        return $sids;
    }

    public function getSitesIfExists(){
        $items = $this->getSites(array('ACTIVE' => 'Y'));
        if (!empty($items)){
            return $items;
        }
        $this->throwException(__METHOD__, 'Active sites not found');
    }

}