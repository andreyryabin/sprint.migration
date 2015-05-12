<?php

namespace Sprint\Migration;

abstract class Version
{

    protected $description = "";

    private $stepMethods = array();

    abstract public function up();

    abstract public function down();

    public function getDescription() {
        return (string)$this->description;
    }


    public function registerStepMethod($name, $cntSteps, $timeout){
        $class = get_class($this);
        $this->stepMethods[] = array('class'=>$class, 'method' => $name, 'steps' => $cntSteps, 'timeout' => $timeout);
    }

    public function getStepMethods(){
        return $this->stepMethods;
    }
}



