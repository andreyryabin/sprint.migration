<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\AbstractBuilder;

class SchemaImport extends AbstractBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }

    protected function initialize() {
        $this->setTitle('SchemaImport');
        $this->setGroup('schema');
    }


    protected function execute() {

    }

}