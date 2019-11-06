<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Locale;
use Sprint\Migration\VersionManager;

class TransferBuilder extends AbstractBuilder
{

    protected function isBuilderEnabled()
    {
        return true;
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_Transfer1'));
        $this->setGroup('configurator');

        $configFrom = $this->getVersionConfig()->getName();
        $items = $this->getVersionConfig()->getList();
        $structure = [];
        foreach ($items as $item) {
            if ($item['name'] != $configFrom) {
                $structure[] = [
                    'title' => $item['title'],
                    'value' => $item['name'],
                ];
            }
        }

        $this->addField('transfer_status', [
            'title' => Locale::getMessage('BUILDER_TransferSelect'),
            'placeholder' => '',
            'width' => 250,
            'select' => [
                [
                    'title' => Locale::getMessage('BUILDER_TransferInstalled'),
                    'value' => VersionEnum::STATUS_INSTALLED,
                ],
                [
                    'title' => Locale::getMessage('BUILDER_TransferNew'),
                    'value' => VersionEnum::STATUS_NEW,
                ],
                [
                    'title' => Locale::getMessage('BUILDER_TransferUnknown'),
                    'value' => VersionEnum::STATUS_UNKNOWN,
                ],
                [
                    'title' => Locale::getMessage('BUILDER_TransferAll'),
                    'value' => 'all',
                ],
            ],
        ]);

        $this->addField('transfer_to', [
            'title' => Locale::getMessage('BUILDER_TransferTo'),
            'placeholder' => '',
            'width' => 250,
            'select' => $structure,
        ]);
    }

    /**
     * @throws ExchangeException
     */
    protected function execute()
    {
        $configFrom = $this->getVersionConfig()->getName();
        $configTo = $this->getFieldValue('transfer_to');

        $this->exitIfEmpty($configTo, Locale::getMessage('BUILDER_TransferEmptyDest'));

        if ($configTo == $configFrom) {
            $this->exitWithMessage(Locale::getMessage('BUILDER_TransferBadDest'));
        }

        if (!$this->getVersionConfig()->isExists($configTo)) {
            $this->exitWithMessage(Locale::getMessage('BUILDER_TransferBadDest'));
        }

        $vmFrom = new VersionManager($configFrom);
        $vmTo = new VersionManager($configTo);

        $status = $this->getFieldValue('transfer_status');
        if (in_array($status, ['all'])) {
            $filter = [];
        } else {
            $filter = ['status' => $status];
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

        $this->outSuccess(Locale::getMessage('BUILDER_TransferCnt', ['#CNT#' => $cnt]));
    }
}
