<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\HelperManager;

class CacheCleaner extends AbstractBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }


    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_CacheCleaner1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_CacheCleaner2'));

    }

    protected function execute() {
        if (\BXClearCache(true)){
            $this->outSuccess('Success');
        } else {
            $this->outError('Error');
        }


    }
}
