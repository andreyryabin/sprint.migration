<?php

namespace Sprint\Migration;

class Upgrade0001 extends Upgrade {

    public function doUpgrade(){

        $versionTable = new VersionTable();
        $versionTable->install();

    }

}