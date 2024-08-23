<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Iblock()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_IblockExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_IblockExport2'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Iblock'));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws RebuildException
     * @throws MigrationException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $iblockId = $this->addFieldAndReturn(
            'iblock_id', [
                'title'       => Locale::getMessage('BUILDER_IblockExport_IblockId'),
                'placeholder' => '',
                'width'       => 250,
                'items'       => $this->getHelperManager()->IblockExchange()->getIblocksStructure(),
            ]
        );

        $iblock = $helper->Iblock()->exportIblock($iblockId);
        if (empty($iblock)) {
            $this->rebuildField('iblock_id');
        }

        $what = $this->addFieldAndReturn(
            'what', [
                'title'    => Locale::getMessage('BUILDER_IblockExport_What'),
                'width'    => 250,
                'multiple' => 1,
                'value'    => [],
                'select'   => [
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
            ]
        );

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
            $struct = $this->getHelperManager()->IblockExchange()->getIblockPropertiesStructure($iblockId);
            if (!empty($struct)) {
                $exportProps = $this->addFieldAndReturn(
                    'export_props', [
                        'title'    => Locale::getMessage('BUILDER_IblockExport_Properties'),
                        'width'    => 250,
                        'multiple' => 1,
                        'value'    => [],
                        'select'   => $struct,
                    ]
                );

                $iblockProperties = $helper->Iblock()->exportProperties($iblockId);
                $iblockProperties = array_filter(
                    $iblockProperties,
                    function ($item) use ($exportProps) {
                        return in_array($item['CODE'], $exportProps);
                    }
                );
            }
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
            $exportElementGrid = $helper->UserOptions()->exportElementGrid($iblockId);
            $exportSectionGrid = $helper->UserOptions()->exportSectionGrid($iblockId);
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/IblockExport.php',
            [
                'iblockExport'      => $iblockExport,
                'iblock'            => $iblock,
                'iblockType'        => $iblockType,
                'iblockFields'      => $iblockFields,
                'iblockPermissions' => $iblockPermissions,
                'iblockProperties'  => $iblockProperties,
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
