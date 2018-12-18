<?php

namespace Sprint\Migration;

trait OutTrait
{

    public function out($msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'out'), func_get_args());
    }

    public function outProgress($msg, $val, $total) {
        call_user_func_array(array('Sprint\Migration\Out', 'outProgress'), func_get_args());
    }

    public function outSuccess($msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outSuccess'), func_get_args());
    }

    public function outError($msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outError'), func_get_args());
    }

    public function outIf($cond, $msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outIf'), func_get_args());
    }

    public function outErrorIf($cond, $msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outErrorIf'), func_get_args());
    }

    public function outSuccessIf($cond, $msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outSuccessIf'), func_get_args());
    }


}
