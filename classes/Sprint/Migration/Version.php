<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exceptions\MigrationException;

class Version
{

    protected $description = "";

    protected $params = array();

    public function up() {
        return true;
    }

    public function down() {
        return true;
    }

    public function getDescription() {
        return $this->description;
    }

    public function out($msg, $var1 = null, $var2 = null){
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'out'), $args);
    }

    public function outProgress($msg, $val, $total){
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'outProgress'), $args);
    }

    public function outSuccess($msg, $var1 = null, $var2 = null){
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'outSuccess'), $args);
    }

    public function outError($msg, $var1 = null, $var2 = null){
        $args = func_get_args();
        call_user_func_array(array('Sprint\Migration\Out', 'outError'), $args);
    }

    public function exitIf($trueCond, $msg ){
        if ($trueCond){
            Throw new MigrationException($msg);
        }
    }

    public function restart(){
        Throw new RestartException();
    }

    /* Need For Sprint\Migration\VersionManager */
    public function getParams(){
        return $this->params;
    }

    /* Need For Sprint\Migration\VersionManager */
    public function setParams($params = array()){
        $this->params = $params;
    }
}



