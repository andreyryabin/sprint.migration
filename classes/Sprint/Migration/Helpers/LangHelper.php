<?php

namespace Sprint\Migration\Helpers;
use Sprint\Migration\Helper;

class LangHelper extends Helper
{

    public function getDefaultLangIds(){
        $by = 'def';
        $order = 'desc';
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CLanguage::GetList($by, $order, array('ACTIVE' => 'Y'));

        $lids = array();
        while ($aItem = $dbRes->Fetch()){
            $lids[] = $aItem['LID'];
        }

        if (empty($lids)){
            $this->addError('languages not found');
        }

        return $lids;
    }
}