<?php

namespace Sprint\Migration\Builders;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockExport extends VersionBuilder
{

    /**
     * @throws LoaderException
     * @return bool
     */
    protected function isBuilderEnabled()
    {
        return (Loader::includeModule('iblock'));
    }

    protected function initialize()
    {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport2'));

        $this->addVersionFields();
    }

    /**
     * @throws ExchangeException
     * @throws HelperException
     * @throws RebuildException
     * @throws BuilderException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();
        $versionName = $this->getVersionName();

        $this->addField('iblock_id', [
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_IblockId'),
            'placeholder' => '',
            'width' => 250,
            'items' => $this->getIblocksStructure(),
        ]);

        $this->addField('what', [
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_What'),
            'width' => 250,
            'multiple' => 1,
            'value' => [],
            'select' => [
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_WhatIblockType'),
                    'value' => 'iblockType',
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_WhatIblock'),
                    'value' => 'iblock',
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_WhatIblockFields'),
                    'value' => 'iblockFields',
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_WhatIblockProperties'),
                    'value' => 'iblockProperties',
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_WhatIblockUserOptions'),
                    'value' => 'iblockUserOptions',
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_WhatIblockElements'),
                    'value' => 'iblockElements',
                ],
            ],
        ]);

        $iblockId = $this->getFieldValue('iblock_id');
        if (empty($iblockId)) {
            $this->rebuildField('iblock_id');
        }

        $iblock = $helper->Iblock()->exportIblock($iblockId);
        if (empty($iblock)) {
            $this->rebuildField('iblock_id');
        }

        $what = $this->getFieldValue('what');
        if (!empty($what)) {
            $what = is_array($what) ? $what : [$what];
        } else {
            $this->rebuildField('what');
        }

        $iblockExport = false;
        $iblockType = [];
        $iblockProperties = [];
        $iblockFields = [];

        $exportElementForm = [];
        $exportSectionForm = [];
        $exportElementList = [];
        $exportSectionList = [];

        if (in_array('iblock', $what)) {
            $iblockExport = true;
        }

        if (in_array('iblockType', $what)) {
            $iblockType = $helper->Iblock()->exportIblockType($iblock['IBLOCK_TYPE_ID']);
        }

        $props = $helper->Iblock()->getProperties($iblockId);
        if (in_array('iblockProperties', $what) && !empty($props)) {
            $this->addField('property_ids', [
                'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_PropertyIds'),
                'width' => 250,
                'multiple' => 1,
                'value' => [],
                'items' => $this->getPropsStructure($props),
            ]);

            $propertyIds = $this->getFieldValue('property_ids');
            if (!empty($propertyIds)) {
                $propertyIds = is_array($propertyIds) ? $propertyIds : [$propertyIds];
            } else {
                $this->rebuildField('property_ids');
            }

            $iblockProperties = $helper->Iblock()->exportProperties($iblockId, [
                'ID' => $propertyIds,
            ]);
        }

        if (in_array('iblockFields', $what)) {
            $iblockFields = $helper->Iblock()->exportIblockFields($iblockId);
        }

        if (in_array('iblockUserOptions', $what)) {
            $exportElementForm = $helper->UserOptions()->exportElementForm($iblockId);
            $exportSectionForm = $helper->UserOptions()->exportSectionForm($iblockId);
            $exportElementList = $helper->UserOptions()->exportElementList($iblockId);
            $exportSectionList = $helper->UserOptions()->exportSectionList($iblockId);
        }

        if (in_array('iblockElements', $what)) {
            $exchange = new \Sprint\Migration\Exchange\IblockExport($this);
            $exchange->from($iblockId);
            $exchange->to($this->getVersionResources($versionName) . '/iblock_elements.xml');
            $exchange->start();
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/IblockExport.php',
            [
                'version' => $versionName,
                'iblockExport' => $iblockExport,
                'iblock' => $iblock,
                'iblockType' => $iblockType,
                'iblockFields' => $iblockFields,
                'iblockProperties' => $iblockProperties,
                'exportElementForm' => $exportElementForm,
                'exportSectionForm' => $exportSectionForm,
                'exportElementList' => $exportElementList,
                'exportSectionList' => $exportSectionList,
            ]
        );
    }


    /**
     * Структура инфоблоков для построения выпадающего списка
     * @return array
     */
    public function getIblocksStructure()
    {
        $helper = $this->getHelperManager();
        $iblockTypes = $helper->Iblock()->getIblockTypes();

        $structure = [];
        foreach ($iblockTypes as $iblockType) {
            $structure[$iblockType['ID']] = [
                'title' => '[' . $iblockType['ID'] . '] ' . $iblockType['LANG'][LANGUAGE_ID]['NAME'],
                'items' => [],
            ];
        }

        $iblocks = $helper->Iblock()->getIblocks();
        foreach ($iblocks as $iblock) {
            $structure[$iblock['IBLOCK_TYPE_ID']]['items'][] = [
                'title' => '[' . $iblock['CODE'] . '] ' . $iblock['NAME'],
                'value' => $iblock['ID'],
            ];
        }

        return $structure;
    }


    /**
     * @param array $props
     * @return array
     */
    protected function getPropsStructure($props = [])
    {
        $structure = [
            0 => ['items' => []],
        ];

        foreach ($props as $prop) {
            $structure[0]['items'][] = [
                'title' => '[' . $prop['CODE'] . '] ' . $prop['NAME'],
                'value' => $prop['ID'],
            ];
        }

        return $structure;
    }

}