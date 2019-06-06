<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;
use CDBResult;
use Sprint\Migration\Exceptions\HelperException;

class Helper
{

    use OutTrait {
        out as protected;
        outIf as protected;
        outProgress as protected;
        outNotice as protected;
        outNoticeIf as protected;
        outInfo as protected;
        outInfoIf as protected;
        outSuccess as protected;
        outSuccessIf as protected;
        outWarning as protected;
        outWarningIf as protected;
        outError as protected;
        outErrorIf as protected;
        outDiff as protected;
        outDiffIf as protected;
    }

    /**
     * @deprecated
     * @var string
     */
    public $lastError = '';

    private $mode = [
        'test' => 0,
        'out_equal' => 0,
    ];

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
            return isset($this->mode[$key]) ? $this->mode[$key] : 0;
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

    protected function throwException($method, $msg, $var1 = null, $var2 = null)
    {
        $args = func_get_args();
        $method = array_shift($args);
        $msg = call_user_func_array('sprintf', $args);

        $msg = $this->getMethod($method) . ': ' . strip_tags($msg);

        $this->lastError = $msg;

        Throw new HelperException($msg);
    }

    protected function hasDiff($exists, $fields)
    {
        return ($exists != $fields);
    }

    protected function checkModules($names = [])
    {
        $names = is_array($names) ? $names : [$names];
        foreach ($names as $name) {
            if (!Loader::includeModule($name)) {
                $this->throwException(__METHOD__, "module %s not installed", $name);
            }
        }
    }

    protected function checkRequiredKeys($method, $fields, $reqKeys = [])
    {
        foreach ($reqKeys as $name) {
            if (empty($fields[$name])) {
                $this->throwException($method, 'requred key "%s" empty', $name);
            }
        }
    }

    /**
     * @param CDBResult $dbres
     * @param bool $indexKey
     * @param bool $valueKey
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

    private function getMethod($method)
    {
        $path = explode('\\', $method);
        $short = array_pop($path);
        return $short;
    }

}