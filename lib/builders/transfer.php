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
        $this->setGroup('configurator');


        $configFrom = $this->getVersionConfig()->getName();
        $items = $this->getVersionConfig()->getList();
        $structure = [];
        foreach ($items as $item) {
            if ($item['name'] != $configFrom) {
                $structure[] = [
                    'title' => $item['title'],
                    'value' => $item['name']
                ];
            }
        }

        $this->addField('transfer_status', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_TransferSelect'),
            'placeholder' => '',
            'width' => 250,
            'select' => [
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_TransferInstalled'),
                    'value' => 'installed',
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_TransferNew'),
                    'value' => 'new',
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_TransferAll'),
                    'value' => 'all',
                ],
            ]
        ));

        $this->addField('transfer_to', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_TransferTo'),
            'placeholder' => '',
            'width' => 250,
            'select' => $structure
        ));


    }

    protected function execute() {

        $configFrom = $this->getVersionConfig()->getName();
        $configTo = $this->getFieldValue('transfer_to');

        $this->exitIfEmpty($configTo, GetMessage('SPRINT_MIGRATION_BUILDER_TransferEmptyDest'));

        if ($configTo == $configFrom) {
            $this->exitWithMessage(GetMessage('SPRINT_MIGRATION_BUILDER_TransferBadDest'));
        }

        if (!$this->getVersionConfig()->isExists($configTo)) {
            $this->exitWithMessage(GetMessage('SPRINT_MIGRATION_BUILDER_TransferBadDest'));
        }


        $vmFrom = new VersionManager($configFrom);
        $vmTo = new VersionManager($configTo);

        $status = $this->getFieldValue('transfer_status');
        if (in_array($status, array('installed', 'new'))) {
            $filter = array('status' => $status);
        } else {
            $filter = array();
        }


        $versions = $vmFrom->getVersions($filter);

        $cnt = 0;
        foreach ($versions as $meta) {

            if ($meta['is_file']) {
                $source = $meta['location'];
                $dest = $vmTo->getVersionFile($meta['version']);

                if (is_file($dest)) {
                    unlink($source);
                } else {
                    rename($source, $dest);
                }
            }


            if ($meta['is_record']) {
                $vmFrom->getVersionTable()->removeRecord($meta);
                $vmTo->getVersionTable()->addRecord($meta);
            }


            $cnt++;
        }


        $this->outSuccess(GetMessage('SPRINT_MIGRATION_BUILDER_TransferCnt', array('#CNT#' => $cnt)));
    }
}
