<?php

namespace Sprint\Migration;

class Upgrade
{

    protected $debug = false;

    public function setDebug($debug = false){
        $this->debug = $debug;
    }

    public function doUpgrade() {
        //
    }

    protected function isMssql(){
        return Module::isMssql();
    }

    protected function isWin1251(){
        return Module::isWin1251();
    }
}
