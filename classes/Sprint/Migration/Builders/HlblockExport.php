<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Exceptions\HelperException;

class HlblockExport extends AbstractBuilder
{

    public function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport2'));
        $this->setTemplateFile(Module::getModuleDir() . '/templates/HlblockExport.php');
    }


    public function execute() {
        $helper = new HelperManager();


    }
}