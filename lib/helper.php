<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Helper
{

    use OutTrait;

    /**
     * @deprecated
     * @var string
     */
    public $lastError = '';

    private $mode = array(
        'test' => 0,
        'out_equal' => 0,
    );

    /**
     * @deprecated
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }

    public function getMode($key = false) {
        if ($key) {
            return isset($this->mode[$key]) ? $this->mode[$key] : 0;
        } else {
            return $this->mode;
        }
    }

    public function setMode($key, $val = 1) {
        if ($key instanceof Helper) {
            $this->mode = $key->getMode();
        } else {
            $val = ($val) ? 1 : 0;
            $this->mode[$key] = $val;
        }
    }

    public function setTestMode($val = 1) {
        $this->setMode('test', $val);
    }

    public function throwException($method, $msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        $method = array_shift($args);
        $msg = call_user_func_array('sprintf', $args);

        $msg = $this->getMethod($method) . ': ' . strip_tags($msg);

        $this->lastError = $msg;

        Throw new HelperException($msg);
    }

    public function isEnabled() {
        return true;
    }

    protected function hasDiff($exists, $fields) {
        return ($exists != $fields);
    }

    protected function checkModules($names = array()) {
        $names = is_array($names) ? $names : array($names);
        foreach ($names as $name) {
            if (!\CModule::IncludeModule($name)) {
                $this->throwException(__METHOD__, "module %s not installed", $name);
            }
        }
    }

    protected function checkRequiredKeys($method, $fields, $reqKeys = array()) {
        foreach ($reqKeys as $name) {
            if (empty($fields[$name])) {
                $this->throwException($method, 'requred key "%s" empty', $name);
            }
        }
    }

    /**
     * @param \CDBResult $dbres
     * @param bool $indexKey
     * @param bool $valueKey
     * @return array
     */
    protected function fetchAll(\CDBResult $dbres, $indexKey = false, $valueKey = false) {
        $res = array();

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

    private function getMethod($method) {
        $path = explode('\\', $method);
        $short = array_pop($path);
        return $short;
    }

}