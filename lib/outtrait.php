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

    public function outNotice($msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outNotice'), func_get_args());
    }

    public function outInfo($msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outInfo'), func_get_args());
    }

    public function outInfoIf($msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outInfoIf'), func_get_args());
    }

    public function outSuccess($msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outSuccess'), func_get_args());
    }

    public function outWarning($msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outWarning'), func_get_args());
    }

    public function outError($msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outError'), func_get_args());
    }

    public function outIf($cond, $msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outIf'), func_get_args());
    }

    public function outWarningIf($cond, $msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outWarningIf'), func_get_args());
    }

    public function outNoticeIf($cond, $msg, $var1 = null, $var2 = null) {
        call_user_func_array(array('Sprint\Migration\Out', 'outNoticeIf'), func_get_args());
    }

    public function outDiff($arr1, $arr2) {
        call_user_func_array(array('Sprint\Migration\Out', 'outDiff'), func_get_args());
    }

    public function outDiffIf($cond, $arr1, $arr2) {
        call_user_func_array(array('Sprint\Migration\Out', 'outDiffIf'), func_get_args());
    }

}
