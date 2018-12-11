<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\VersionManager;

class Marker extends AbstractBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }


    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_MARK'));
        $this->setGroup('configurator');

        $this->addField('mark_version', array(
            'title' => GetMessage('SPRINT_MIGRATION_MARK_FIELD1'),
            'placeholder' => GetMessage('SPRINT_MIGRATION_MARK_VERSION'),
            'width' => 250,
        ));

        $this->addField('mark_status', array(
            'title' => GetMessage('SPRINT_MIGRATION_MARK_FIELD2'),
            'placeholder' => '',
            'width' => 250,
            'select' => array(
                array(
                    'title' => GetMessage('SPRINT_MIGRATION_MARK_AS_INSTALLED'),
                    'value' => 'installed'
                ),
                array(
                    'title' => GetMessage('SPRINT_MIGRATION_MARK_AS_NEW'),
                    'value' => 'new'
                ),
            )
        ));

    }

    protected function execute() {
        $version = $this->getFieldValue('mark_version');
        $status = $this->getFieldValue('mark_status');

        $versionManager = new VersionManager($this->getVersionConfig()->getName());
        $markresult = $versionManager->markMigration($version, $status);

        foreach ($markresult as $val) {
            if ($val['success']) {
                $this->outSuccess($val['message']);
            } else {
                $this->outError($val['message']);
            }
        }

    }
}
