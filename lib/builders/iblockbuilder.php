<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Builders\Traits\IblocksStructureTrait;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockBuilder extends VersionBuilder
{
    use IblocksStructureTrait;

    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Iblock()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_IblockExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_IblockExport2'));
        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws RebuildException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $this->addField('iblock_id', [
            'title' => Locale::getMessage('BUILDER_IblockExport_IblockId'),
            'placeholder' => '',
            'width' => 250,
            'items' => $this->getIblocksStructure(),
        ]);

        $this->addField('what', [
            'title' => Locale::getMessage('BUILDER_IblockExport_What'),
            'width' => 250,
            'multiple' => 1,
            'value' => [],
            'select' => [
                [
                    'title' => Locale::getMessage('BUILDER_IblockExport_WhatIblockType'),
                    'value' => 'iblockType',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_IblockExport_WhatIblock'),
                    'value' => 'iblock',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_IblockExport_WhatIblockFields'),
                    'value' => 'iblockFields',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_IblockExport_WhatIblockProperties'),
                    'value' => 'iblockProperties',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_IblockExport_WhatIblockUserOptions'),
                    'value' => 'iblockUserOptions',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_IblockExport_WhatIblockPermissions'),
                    'value' => 'iblockPermissions',
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
        $iblockPermissions = [];

        $exportElementForm = [];
        $exportSectionForm = [];
        $exportElementList = [];
        $exportSectionList = [];

        $exportElementGrid = [];
        $exportSectionGrid = [];

        if (in_array('iblock', $what)) {
            $iblockExport = true;
        }

        if (in_array('iblockType', $what)) {
            $iblockType = $helper->Iblock()->exportIblockType($iblock['IBLOCK_TYPE_ID']);
        }

        if (in_array('iblockProperties', $what)) {
            $this->addField('property_ids', [
                'title' => Locale::getMessage('BUILDER_IblockExport_PropertyIds'),
                'width' => 250,
                'multiple' => 1,
                'value' => [],
                'select' => $this->getIblockPropertiesStructure($iblockId),
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

        if (in_array('iblockPermissions', $what)) {
            $iblockPermissions = $helper->Iblock()->exportGroupPermissions($iblockId);
        }

        if (in_array('iblockUserOptions', $what)) {
            $exportElementForm = $helper->UserOptions()->exportElementForm($iblockId);
            $exportSectionForm = $helper->UserOptions()->exportSectionForm($iblockId);
            $exportElementList = $helper->UserOptions()->exportElementList($iblockId);
            $exportSectionList = $helper->UserOptions()->exportSectionList($iblockId);

            $exportElementGrid = $helper->UserOptions()->exportGrid(
                $helper->UserOptions()->getElementGridId($iblockId)
            );
            $exportSectionGrid = $helper->UserOptions()->exportGrid(
                $helper->UserOptions()->getSectionGridId($iblockId)
            );
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/IblockExport.php',
            [
                'iblockExport' => $iblockExport,
                'iblock' => $iblock,
                'iblockType' => $iblockType,
                'iblockFields' => $iblockFields,
                'iblockPermissions' => $iblockPermissions,
                'iblockProperties' => $iblockProperties,
                'exportElementForm' => $exportElementForm,
                'exportSectionForm' => $exportSectionForm,
                'exportElementList' => $exportElementList,
                'exportSectionList' => $exportSectionList,
                'exportElementGrid' => $exportElementGrid,
                'exportSectionGrid' => $exportSectionGrid,
            ]
        );
    }
}