<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class LangHelper extends Helper
{

    public function getDefaultLangIdIfExists(){
        $by = 'def';
        $order = 'desc';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $aItem = \CLanguage::GetList($by, $order, array('ACTIVE' => 'Y'))->Fetch();

        if ($aItem) {
            return $aItem['LID'];
        }

        $this->throwException(__METHOD__, 'Default language not found');
    }

    public function getLangs($filter = array()) {
        $by = 'def';
        $order = 'desc';

        $lids = array();
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CLanguage::GetList($by, $order, $filter);
        while ($aItem = $dbRes->Fetch()) {
            $lids[] = $aItem;
        }

        return $lids;
    }
}