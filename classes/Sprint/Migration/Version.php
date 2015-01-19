<?php

namespace Sprint\Migration;

abstract class Version
{

    protected $description = "";

    abstract public function up();

    abstract public function down();

    public function getDescription() {
        return (string)$this->description;
    }

}



