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

        $path = explode('\\', $method);
        $short = array_pop($path);

        $msg = $short . ': ' . strip_tags($msg);

        $this->lastError = $msg;

        Throw new HelperException($msg);
    }


}