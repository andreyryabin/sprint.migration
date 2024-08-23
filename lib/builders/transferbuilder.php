<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\VersionConfig;
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
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Tools'));

        $this->addField('transfer_filter', [
            'title'       => Locale::getMessage('BUILDER_TransferSelect'),
            'placeholder' => '',
            'width'       => 250,
            'select'      => $this->getFilters(),
            'value'       => VersionEnum::STATUS_UNKNOWN,
        ]);

        $this->addField('transfer_to', [
            'title'       => Locale::getMessage('BUILDER_TransferTo'),
            'placeholder' => '',
            'width'       => 250,
            'select'      => $this->getConfigs(),
        ]);
    }

    /**
     * @throws MigrationException
     * @throws RebuildException
     */
    protected function execute()
    {
        $transferFilter = $this->getFieldValue('transfer_filter');
        $transferTo = $this->getFieldValue('transfer_to');

        if (!$transferFilter || !$transferTo) {
            $this->rebuildField('transfer_to');
        }

        $vmFrom = new VersionManager(
            $this->getVersionConfig()
        );

        $vmTo = new VersionManager(
            new VersionConfig($transferTo)
        );

        $transferresult = $vmFrom->transferMigration(
            $transferFilter,
            $vmTo
        );

        $cnt = 0;
        foreach ($transferresult as $item) {
            if ($item['success']) {
                $cnt++;
            }
        }

        $this->outSuccess(
            Locale::getMessage(
                'TRANSFER_OK_CNT',
                [
                    '#CNT#' => $cnt,
                ]
            )
        );
    }

    protected function getConfigs(): array
    {
        $structure = [];
        $configFrom = $this->getVersionConfig()->getName();
        foreach ($this->getVersionConfig()->getList() as $item) {
            if ($item['name'] != $configFrom) {
                $structure[] = [
                    'title' => $item['title'],
                    'value' => $item['name'],
                ];
            }
        }
        return $structure;
    }

    private function getFilters(): array
    {
        return [
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
        ];
    }
}
