<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Exceptions\HelperException;

class EventMessages extends VersionBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }

    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_EventMessages1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_EventMessages2'));


    }


    protected function execute() {
        $helper = new HelperManager();

    }


}