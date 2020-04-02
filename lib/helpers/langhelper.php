<?php

namespace Sprint\Migration\Helpers;

use CLanguage;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class LangHelper extends Helper
{

    /**
     * @throws HelperException
     * @return mixed
     */
    public function getDefaultLangIdIfExists()
    {
        $by = 'def';
        $order = 'desc';

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $item = CLanguage::GetList($by, $order, ['ACTIVE' => 'Y'])->Fetch();

        if ($item) {
            return $item['LID'];
        }

        $this->throwException(
            __METHOD__,
            Locale::getMessage(
                'ERR_DEFAULT_LANGUAGE_NOT_FOUND'
            )
        );

        return false;
    }

    /**
     * @param array $filter
     * @return array
     */
    public function getLangs($filter = [])
    {
        $by = 'def';
        $order = 'desc';

        $lids = [];
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = CLanguage::GetList($by, $order, $filter);
        while ($item = $dbres->Fetch()) {
            $lids[] = $item;
        }

        return $lids;
    }

    /**
     * @throws HelperException
     * @return array
     */
    public function getLangsIfExists()
    {
        $items = $this->getLangs(['ACTIVE' => 'Y']);
        if (!empty($items)) {
            return $items;
        }

        $this->throwException(
            __METHOD__,
            Locale::getMessage(
                'ERR_ACTIVE_LANGUAGES_NOT_FOUND'
            )
        );
        return [];
    }
}
