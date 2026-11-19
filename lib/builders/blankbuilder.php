<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class BlankBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_Version1'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Tools'));

        $this->addVersionFields();
    }

    protected function execute()
    {
        $this->createVersionFile(
            Module::getModuleTemplateFile('version'),
            [],
            false,
        );
    }
}
