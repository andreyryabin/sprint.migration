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

    public function getLangsIfExists() {
        $by = 'def';
        $order = 'desc';

        $lids = array();
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CLanguage::GetList($by, $order, array('ACTIVE' => 'Y'));
        while ($aItem = $dbRes->Fetch()) {
            $lids[] = $aItem;
        }

        if (!empty($lids)) {
            return $lids;
        }

        $this->throwException(__METHOD__, 'Languages not found');
    }
}