<?php

namespace Sprint\Migration;

class Upgrade0001 extends Upgrade {

    public function doUpgrade(){

        $db = new Db();

        $db->install();

    }

}