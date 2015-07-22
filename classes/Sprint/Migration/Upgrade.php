<?php

namespace Sprint\Migration;

abstract class Upgrade extends Db
{

    protected $debug = false;

    public function setDebug($debug = false){
        $this->debug = $debug;
    }

    abstract public function doUpgradeMysql();
    abstract public function doUpgradeMssql();
}
