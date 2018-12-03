<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

class Helper
{

    /**
     * @deprecated
     * @var string
     */
    public $lastError = '';

    /**
     * @deprecated
     * @return string
     */
    public function getLastError() {
        return $this->lastError;
    }

    protected function throwException($method, $msg, $var1 = null, $var2 = null) {
        $args = func_get_args();
        $method = array_shift($args);
        $msg = call_user_func_array('sprintf', $args);

        $msg = $this->getMethod($method) . ': ' . strip_tags($msg);

        $this->lastError = $msg;

        Throw new HelperException($msg);
    }

    protected function checkRequiredKeys($method, $fields, $reqKeys = array()) {
        foreach ($reqKeys as $name) {
            if (empty($fields[$name])) {
                $msg = sprintf('%s: requred key "%s" empty', $this->getMethod($method), $name);
                Throw new HelperException($msg);
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