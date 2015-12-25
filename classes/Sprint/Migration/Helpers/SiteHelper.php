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

    public function getSitesIfExists() {
        $by = 'def';
        $order = 'desc';

        $sids = array();
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CSite::GetList($by, $order, array('ACTIVE' => 'Y'));
        while ($aItem = $dbRes->Fetch()) {
            $sids[] = $aItem;
        }

        if (!empty($sids)) {
            return $sids;
        }

        $this->throwException(__METHOD__, 'Sites not found');
    }

}