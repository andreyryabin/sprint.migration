<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\Restart;

abstract class Version
{

    protected $description = "";

    protected $params = array();

    abstract public function up();

    abstract public function down();


    public function getDescription() {
        return (string)$this->description;
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

    public function restart(){
        Throw new Restart();
    }

    /* Need For Sprint\Migration\Manager */
    public function getParams(){
        return $this->params;
    }

    /* Need For Sprint\Migration\Manager */
    public function setParams($params = array()){
        $this->params = $params;
    }
}



