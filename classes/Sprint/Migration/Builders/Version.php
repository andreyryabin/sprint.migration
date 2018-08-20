<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;

class Version extends VersionBuilder
{

    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_Version1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_Version2'));

        $this->setField('prefix', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_PREFIX'),
            'value' => $this->getConfigVal('version_prefix'),
            'width' => 250,
        ));

        $this->setField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ));
    }

    protected function execute() {
        $template = $this->getConfigVal('migration_template');
        if ($template && is_file(Module::getDocRoot() . $template)) {
            $template = Module::getDocRoot() . $template;
        } else {
            $template = Module::getModuleDir() . '/templates/version.php';
        }

        $this->createVersionFile($template, array());
    }
}
