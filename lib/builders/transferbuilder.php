<?php

namespace Sprint\Migration\Builders;

use Exception;
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
        $this->setGroup('Tools');

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

        $this->addField('transfer_filter', [
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
     * @throws Exception
     */
    protected function execute()
    {
        $vmFrom = new VersionManager(
            $this->getVersionConfig()->getName()
        );

        $vmTo = new VersionManager(
            $this->getFieldValue('transfer_to')
        );

        $transferresult = $vmFrom->transferMigration(
            $this->getFieldValue('transfer_filter'),
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
}
