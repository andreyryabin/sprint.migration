<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Fields\RadioListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockBuilder extends VersionBuilder
{
    protected function initialize(): void
    {
        $this->setEnabled($this->getHelperManager()->Iblock()->isEnabled());
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
    protected function execute(): void
    {
        $helper = $this->getHelperManager();
        $exhelper = $this->getExchangeHelpers();

        $iblockId = (int) $this->askField('iblock_id',
            (new RadioListField(Locale::getMessage('BUILDER_IblockExport_IblockId')))
                ->setOptions($exhelper->IblockExchange()->getIblocksSelect(), 'ID', 'TITLE', 'GROUP'),
        );

        $iblock = $helper->Iblock()->exportIblock($iblockId);
        if (empty($iblock)) {
            $this->resetField('iblock_id');
        }

        $what = $this->askField('what',
            (new CheckboxListField(Locale::getMessage('BUILDER_IblockExport_What')))
                ->addOption('iblockType', Locale::getMessage('BUILDER_IblockExport_WhatIblockType'))
                ->addOption('iblock', Locale::getMessage('BUILDER_IblockExport_WhatIblock'))
                ->addOption('iblockFields', Locale::getMessage('BUILDER_IblockExport_WhatIblockFields'))
                ->addOption('iblockProperties', Locale::getMessage('BUILDER_IblockExport_WhatIblockProperties'))
                ->addOption('iblockUserOptions', Locale::getMessage('BUILDER_IblockExport_WhatIblockUserOptions'))
                ->addOption('iblockPermissions', Locale::getMessage('BUILDER_IblockExport_WhatIblockPermissions')),
        );

        $iblockExport = false;
        $iblockType = [];
        $iblockProperties = [];
        $iblockFields = [];
        $iblockPermissions = [];
        $extendedPermissions = [];

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
            $propertiesSelect = $exhelper->IblockExchange()->getIblockPropertiesSelect($iblockId);
            if (!empty($propertiesSelect)) {
                $exportProps = $this->askField('export_props',
                    (new CheckboxListField(Locale::getMessage('BUILDER_IblockExport_Properties')))
                        ->setOptions($propertiesSelect, 'CODE', 'TITLE'),
                );

                $iblockProperties = $helper->Iblock()->exportProperties($iblockId);
                $iblockProperties = array_filter(
                    $iblockProperties,
                    function ($item) use ($exportProps) {
                        return in_array($item['CODE'], $exportProps);
                    },
                );
            }
        }

        if (in_array('iblockFields', $what)) {
            $iblockFields = $helper->Iblock()->exportIblockFields($iblockId);
        }

        if (in_array('iblockPermissions', $what)) {
            //\Bitrix\Iblock\IblockTable::RIGHTS_EXTENDED
            if ($iblock['RIGHTS_MODE'] == 'E') {
                $extendedPermissions = $helper->Iblock()->exportExtendedPermissions($iblockId);
            } else {
                $iblockPermissions = $helper->Iblock()->exportGroupPermissions($iblockId);
            }
        }

        if (in_array('iblockUserOptions', $what)) {
            $exportElementForm = $helper->UserOptions()->exportElementForm($iblockId);
            $exportSectionForm = $helper->UserOptions()->exportSectionForm($iblockId);
            $exportElementList = $helper->UserOptions()->exportElementList($iblockId);
            $exportSectionList = $helper->UserOptions()->exportSectionList($iblockId);
            $exportElementGrid = $helper->UserOptions()->exportElementGrid($iblockId);
            $exportSectionGrid = $helper->UserOptions()->exportSectionGrid($iblockId);
        }

        $this->confirmCreateVersion();

        $this->createVersionFile(
            Module::getModuleTemplateFile('IblockExport'),
            [
                'iblockExport'        => $iblockExport,
                'iblock'              => $iblock,
                'iblockType'          => $iblockType,
                'iblockFields'        => $iblockFields,
                'iblockPermissions'   => $iblockPermissions,
                'extendedPermissions' => $extendedPermissions,
                'iblockProperties'    => $iblockProperties,
                'exportElementForm'   => $exportElementForm,
                'exportSectionForm'   => $exportSectionForm,
                'exportElementList'   => $exportElementList,
                'exportSectionList'   => $exportSectionList,
                'exportElementGrid'   => $exportElementGrid,
                'exportSectionGrid'   => $exportSectionGrid,
            ],
        );
    }
}
