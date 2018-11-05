<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\VersionManager;

class Configurator extends AbstractBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }


    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_Configurator'));
        $this->setGroup('configurator');

        $this->addField('config_name', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_Configurator_config_name'),
            'placeholder' => 'example',
            'width' => 250,
        ));
    }

    protected function execute() {
        $configname = $this->getFieldValue('config_name');
        if (empty($configname)) {
            $this->rebuildField('config_name');
        }

        if ($this->getVersionConfig()->createConfig($configname)) {
            $this->redirect('/bitrix/admin/sprint_migrations.php?' . http_build_query(array(
                    'lang' => LANGUAGE_ID,
                    'config' => $configname,
                )));
        } else {
            $this->outError(GetMessage('SPRINT_MIGRATION_BUILDER_Configurator_config_error'));
        }

    }

}
