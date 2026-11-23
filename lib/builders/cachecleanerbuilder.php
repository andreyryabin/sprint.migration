<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Builder;
use Sprint\Migration\Locale;

use function BXClearCache;

class CacheCleanerBuilder extends Builder
{
    protected function isBuilderEnabled(): bool
    {
        return true;
    }

    protected function initialize(): void
    {
        $this->setTitle(Locale::getMessage('BUILDER_CacheCleaner1'));
        $this->setDescription(Locale::getMessage('BUILDER_CacheCleaner2'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Tools'));
    }

    protected function execute(): void
    {
        BXClearCache(true);
        $this->outSuccess('Success');
    }
}
