<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class BlankBuilder extends VersionBuilder
{
    protected function initialize(): void
    {
        $this->setTitle(Locale::getMessage('BUILDER_Version1'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Tools'));

        $this->addVersionFields();
    }

    /**
     * @throws MigrationException
     */
    protected function execute(): void
    {
        $this->createVersionFile(
            Module::getModuleTemplateFile('version'),
            [],
            false,
        );
    }
}
