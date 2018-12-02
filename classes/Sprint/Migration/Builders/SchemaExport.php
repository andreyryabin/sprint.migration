<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\Schema\IblockSchema;

class SchemaExport extends AbstractBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }

    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_SchemaExport'));
        $this->setGroup('schema');
    }


    protected function execute() {
        $schema = new IblockSchema($this->getVersionConfig());

        $schema->export();

        $this->outSuccess('ok');
    }


}