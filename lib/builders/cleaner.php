<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\VersionManager;

class Cleaner extends AbstractBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }

    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_Cleaner'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_Cleaner_desc'));
        $this->setGroup('configurator');

        $configs = $this->getVersionConfig()->getList();

        $structure = array();
        foreach ($configs as $config) {
            $structure[] = array(
                'title' => $config['title'],
                'value' => $config['name'],
            );
        }

        $this->addField('config_name', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_Cleaner_config_name'),
            'select' => $structure,
            'width' => 250,
            'value' => $this->getVersionConfig()->getName()
        ));

    }

    protected function execute() {
        $configname = $this->getFieldValue('config_name');
        if (empty($configname)) {
            $this->rebuildField('config_name');
        }

        if ($this->getVersionConfig()->deleteConfig($configname)) {
            $this->redirect('/bitrix/admin/sprint_migrations.php?' . http_build_query(array(
                    'lang' => LANGUAGE_ID,
                    'config' => 'cfg',
                )));
        } else {
            $this->outError(GetMessage('SPRINT_MIGRATION_BUILDER_Cleaner_config_error'));
        }
    }

}
