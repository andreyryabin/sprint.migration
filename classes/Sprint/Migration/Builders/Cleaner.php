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
            'multiple' => 1,
        ));

    }

    protected function execute() {
        $confignames = $this->getFieldValue('config_name');

        if (!empty($confignames)) {
            $confignames = is_array($confignames) ? $confignames : array($confignames);
        } else {
            $this->rebuildField('config_name');
        }

        foreach ($confignames as $configname) {
            $this->getVersionConfig()->deleteConfig($configname);
        }

        $this->redirect('/bitrix/admin/sprint_migrations.php?' . http_build_query(array(
                'lang' => LANGUAGE_ID,
                'config' => 'cfg',
            )));


    }

}
