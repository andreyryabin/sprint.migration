<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\Locale;
use function BXClearCache;

class CacheCleanerBuilder extends AbstractBuilder
{
    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_CacheCleaner1'));
        $this->setDescription(Locale::getMessage('BUILDER_CacheCleaner2'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Tools'));
    }

    protected function execute()
    {
        if (BXClearCache(true)) {
            $this->outSuccess('Success');
        } else {
            $this->outError('Error');
        }
    }
}
