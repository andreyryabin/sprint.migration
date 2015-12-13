<?php

namespace Sprint\Migration;

class Helper
{

    private $errors = array();
    public $lastError = '';

    public function addError($msg, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        $this->lastError = $msg;
        $this->errors[] = $msg;
    }

    public function getLastError($stripTags=true){
        $lastError = end(array_values($this->errors));
        return ($stripTags) ? strip_tags($lastError) : $lastError;
    }

    public function getErrors($stripTags=true){
        $err = array_values($this->errors);
        if ($stripTags){
            array_walk($err,'strip_tags');
        }
        return $err;
    }

}
