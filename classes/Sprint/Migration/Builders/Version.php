<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\HelperManager;

class Version extends AbstractBuilder
{

    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_Version'));
        $this->setTemplateFile(Module::getModuleDir() . '/templates/version.php');


        $this->setField('prefix', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_PREFIX'),
            'value' => $this->getConfigVal('version_prefix'),
            'width' => 250,
        ));

        $this->setField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'rows' => 3,
        ));
    }

    protected function execute() {
        //
    }
}
