<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\VersionManager;

class Transfer extends AbstractBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }


    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_Transfer1'));
        //$this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_Transfer2'));


        $configFrom = $this->getVersionConfig()->getConfigName();
        $items = $this->getVersionConfig()->getConfigList();
        $structure = [];
        foreach ($items as $item) {
            if ($item['name'] != $configFrom){
                $structure[] = [
                    'title' => $item['title'],
                    'value' => $item['name']
                ];
            }
        }

        $this->addField('transfer_to', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_Transfer2'),
            'placeholder' => '',
            'width' => 250,
            'select' => $structure
        ));

    }

    protected function execute() {

        $configFrom = $this->getVersionConfig()->getConfigName();
        $configTo = $this->getFieldValue('transfer_to');

        $this->exitIfEmpty($configTo, GetMessage('SPRINT_MIGRATION_BUILDER_TransferEmptyDest'));

        if ($configTo == $configFrom) {
            $this->exitWithMessage(GetMessage('SPRINT_MIGRATION_BUILDER_TransferBadDest'));
        }

        if (!$this->getVersionConfig()->isConfigExists($configTo)){
            $this->exitWithMessage(GetMessage('SPRINT_MIGRATION_BUILDER_TransferBadDest'));
        }


        $vmFrom = new VersionManager($configFrom);
        $vmTo = new VersionManager($configTo);

        $versions = $vmFrom->getVersions(array('status' => 'installed'));

        $cnt = 0;
        foreach ($versions as $version) {

            $source = $version['location'];
            $dest = $vmTo->getVersionConfig()->getConfigVal('migration_dir') . '/' . $version['version'] . '.php';

            if (is_file($dest)) {
                unlink($source);
            } else {
                rename($source, $dest);
            }

            $vmFrom->getVersionTable()->removeRecord($version);
            $vmTo->getVersionTable()->addRecord($version);
            $cnt++;
        }


        $this->outSuccess(GetMessage('SPRINT_MIGRATION_BUILDER_TransferCnt', array('#CNT#' => $cnt)));
    }
}
