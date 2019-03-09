<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class LangHelper extends Helper
{

    /**
     * @return mixed
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function getDefaultLangIdIfExists() {
        $by = 'def';
        $order = 'desc';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $item = \CLanguage::GetList($by, $order, array('ACTIVE' => 'Y'))->Fetch();

        if ($item) {
            return $item['LID'];
        }

        $this->throwException(__METHOD__, 'Default language not found');
    }

    /**
     * @param array $filter
     * @return array
     */
    public function getLangs($filter = array()) {
        $by = 'def';
        $order = 'desc';

        $lids = array();
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = \CLanguage::GetList($by, $order, $filter);
        while ($item = $dbres->Fetch()) {
            $lids[] = $item;
        }

        return $lids;
    }

    /**
     * @return array
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function getLangsIfExists() {
        $items = $this->getLangs(array('ACTIVE' => 'Y'));
        if (!empty($items)) {
            return $items;
        }
        $this->throwException(__METHOD__, 'Active langs not found');
    }
}