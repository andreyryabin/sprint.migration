<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CDBResult;
use CMain;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Traits\OutTrait;

class Helper
{
    use OutTrait;

    private int $testMode = 0;

    /**
     * @throws HelperException
     */
    public function __construct()
    {
        if (!$this->isEnabled()) {
            throw new HelperException(Locale::getMessage('ERR_HELPER_DISABLED'));
        }
    }

    public function setTestMode(int $val = 1): Helper
    {
        $this->testMode = $val;
        return $this;
    }

    public function isTestMode(): bool
    {
        return $this->testMode;
    }

    public function isEnabled()
    {
        return true;
    }

    protected function checkModules(array $names = []): bool
    {
        $names = is_array($names) ? $names : [$names];
        foreach ($names as $name) {
            try {
                if (!Loader::includeModule($name)) {
                    return false;
                }
            } catch (LoaderException $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * @throws HelperException
     * @deprecated
     */
    protected function throwException()
    {
        throw new HelperException();
    }

    /**
     * @throws HelperException
     */
    protected function throwApplicationExceptionIfExists()
    {
        /* @global $APPLICATION CMain */
        global $APPLICATION;
        if ($APPLICATION->GetException()) {
            throw new HelperException(
                $APPLICATION->GetException()->GetString()
            );
        }
    }

    protected function hasDiff($exists, $fields): bool
    {
        return ($exists != $fields);
    }

    /**
     * @param $exists
     * @param $fields
     *
     * @return bool
     */
    protected function hasDiffStrict($exists, $fields): bool
    {
        return ($exists !== $fields);
    }

    /**
     * @param array $fields
     * @param array $reqKeys
     *
     * @throws HelperException
     */
    protected function checkRequiredKeys($fields, $reqKeys = [])
    {
        if (is_string($fields)) {
            throw new HelperException('Old format for checkRequiredKeys');
        }

        foreach ($reqKeys as $name) {
            if (empty($fields[$name])) {
                throw new HelperException(
                    Locale::getMessage(
                        'ERR_EMPTY_REQ_FIELD',
                        [
                            '#NAME#' => $name,
                        ]
                    )
                );
            }
        }
    }

    /**
     * @param CDBResult $dbres
     * @param bool      $indexKey
     * @param bool      $valueKey
     *
     * @return array
     */
    protected function fetchAll(CDBResult $dbres, $indexKey = false, $valueKey = false)
    {
        $res = [];

        while ($item = $dbres->Fetch()) {
            if ($valueKey) {
                $value = $item[$valueKey];
            } else {
                $value = $item;
            }

            if ($indexKey) {
                $indexVal = $item[$indexKey];
                $res[$indexVal] = $value;
            } else {
                $res[] = $value;
            }
        }

        return $res;
    }

    protected function filterByKey($items, $key, $value)
    {
        return array_values(
            array_filter(
                $items,
                function ($item) use ($key, $value) {
                    return ($item[$key] == $value);
                }
            )
        );
    }
}
