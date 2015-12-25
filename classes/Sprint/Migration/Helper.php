<?php

namespace Sprint\Migration;
use Sprint\Migration\Exceptions\HelperException;

class Helper {

    /**
     * @deprecated
     * @var string
     */
    public $lastError = '';

    /**
     * @deprecated
     * @return string
     */
    public function getLastError(){
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

    protected function checkRequiredKeys($method, $fields, $reqKeys = array()){
        foreach ($reqKeys as $name){
            if (!isset($fields[$name])){
                $msg = sprintf('%s: requred key "%s" not found', $this->getMethod($method), $name);
                Throw new HelperException($msg);
            }
        }
    }

    private function getMethod($method){
        $path = explode('\\', $method);
        $short = array_pop($path);
        return $short;
    }
}