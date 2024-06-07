<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CDBResult;
use CMain;
use ReflectionClass;
use Sprint\Migration\Exceptions\HelperException;

class Helper
{
    use OutTrait;

    /**
     * @var string
     * @deprecated
     */
    public  $lastError = '';
    private $mode      = [
        'test' => 0,
    ];

    /**
     * Helper constructor.
     *
     * @throws HelperException
     */
    public function __construct()
    {
        if (!$this->isEnabled()) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_HELPER_DISABLED',
                    [
                        '#NAME#' => $this->getHelperName(),
                    ]
                )
            );
        }
    }

    /**
     * @return string
     * @deprecated
     */
    public function getLastError()
    {
        return $this->lastError;
    }

    public function getMode($key = false)
    {
        if ($key) {
            return $this->mode[$key] ?? 0;
        } else {
            return $this->mode;
        }
    }

    public function setMode($key, $val = 1)
    {
        if ($key instanceof Helper) {
            $this->mode = $key->getMode();
        } else {
            $val = ($val) ? 1 : 0;
            $this->mode[$key] = $val;
        }
    }

    public function setTestMode($val = 1)
    {
        $this->setMode('test', $val);
    }

    public function isEnabled()
    {
        return true;
    }

    /**
     * @param array $names
     *
     * @return bool
     */
    protected function checkModules($names = [])
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
     *
     * @param        $method
     * @param        $msg
     * @param string ...$vars
     *
     * @throws HelperException
     * @deprecated
     */
    protected function throwException($method, $msg, ...$vars)
    {
        $args = func_get_args();
        $method = array_shift($args);

        if ($msg instanceof \Throwable) {
            $msg = $msg->getMessage();
        } else {
            $msg = call_user_func_array('sprintf', $args);
            $msg = strip_tags($msg);
        }

        $msg = $this->getMethod($method) . ': ' . $msg;

        $this->lastError = $msg;

        throw new HelperException($msg);
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

    protected function getHelperName()
    {
        return (new ReflectionClass($this))->getShortName();
    }

    protected function hasDiff($exists, $fields)
    {
        return ($exists != $fields);
    }

    /**
     * @param $exists
     * @param $fields
     *
     * @return bool
     */
    protected function hasDiffStrict($exists, $fields)
    {
        return ($exists !== $fields);
    }

    /**
     * @param       $fields
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

    protected function fetchAll(CDBResult $dbres, string $indexKey = '', string $valueKey = ''): array
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

    private function getMethod($method)
    {
        $path = explode('\\', $method);
        $short = array_pop($path);
        return $short;
    }

    protected function merge(array $item, array $default): array
    {
        return array_merge($default, $item);
    }

    protected function mergeCollection(array $collection, array $default): array
    {
        return array_map(function ($item) use ($default) {
            return $this->merge($item, $default);
        }, $collection);
    }

    protected function export(array $item, array $unsetDefault, array $unsetKeys): array
    {
        foreach ($unsetKeys as $key) {
            if (array_key_exists($key, $item)) {
                unset($item[$key]);
            }
        }

        //value может быть null
        foreach ($item as $key => $value) {
            if (array_key_exists($key, $unsetDefault) && $unsetDefault[$key] === $value) {
                unset($item[$key]);
            }
        }

        return $item;
    }

    protected function exportCollection(array $collection, array $unsetDefault, array $unsetKeys): array
    {
        return array_map(function ($item) use ($unsetDefault, $unsetKeys) {
            return $this->export($item, $unsetDefault, $unsetKeys);
        }, $collection);
    }
}
