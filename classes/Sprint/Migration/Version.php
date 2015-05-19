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

    public function restart(){
        Throw new Restart();
    }

    public function getParams(){
        return $this->params;
    }

    public function setParams($params = array()){
        $this->params = $params;
    }
}



