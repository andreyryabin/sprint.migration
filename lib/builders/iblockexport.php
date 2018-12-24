<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Helpers\IblockHelper;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Exceptions\HelperException;

class IblockExport extends VersionBuilder
{

    protected function isBuilderEnabled() {
        return (\CModule::IncludeModule('iblock'));
    }

    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport2'));

        $this->addField('prefix', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_PREFIX'),
            'value' => $this->getVersionConfig()->getVal('version_prefix'),
            'width' => 250,
        ));

        $this->addField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ));

    }

    protected function execute() {
        $helper = HelperManager::getInstance();

        $this->addField('iblock_id', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_IblockId'),
            'placeholder' => '',
            'width' => 250,
            'items' => $this->getIblocksStructure()
        ));

        $this->addField('what', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_What'),
            'width' => 250,
            'multiple' => 1,
            'value' => array(),
            'select' => [
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_WhatIblockType'),
                    'value' => 'iblockType'
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_WhatIblock'),
                    'value' => 'iblock'
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_WhatIblockFields'),
                    'value' => 'iblockFields'
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_WhatIblockProperties'),
                    'value' => 'iblockProperties'
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_WhatIiblockAdminTabs'),
                    'value' => 'iblockAdminTabs'
                ],
            ]
        ));

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
            $what = is_array($what) ? $what : array($what);
        } else {
            $this->rebuildField('what');
        }

        $iblockExport = false;
        $iblockType = array();
        $iblockProperties = array();
        $iblockFields = array();
        $iblockAdminTabs = array();

        if (in_array('iblock', $what)) {
            $iblockExport = true;
        }

        if (in_array('iblockType', $what)) {
            $iblockType = $helper->Iblock()->exportIblockType($iblock['IBLOCK_TYPE_ID']);
        }

        $props = $helper->Iblock()->getProperties($iblockId);
        if (in_array('iblockProperties', $what) && !empty($props)) {
            $this->addField('property_ids', array(
                'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_PropertyIds'),
                'width' => 250,
                'multiple' => 1,
                'value' => array(),
                'items' => $this->getPropsStructure($props)
            ));

            $propertyIds = $this->getFieldValue('property_ids');
            if (!empty($propertyIds)) {
                $propertyIds = is_array($propertyIds) ? $propertyIds : array($propertyIds);
            } else {
                $this->rebuildField('property_ids');
            }

            $iblockProperties = $helper->Iblock()->exportProperties($iblockId, array(
                'ID' => $propertyIds
            ));
        }


        if (in_array('iblockFields', $what)) {
            $iblockFields = $helper->Iblock()->exportIblockFields($iblockId);
        }


        if (in_array('iblockAdminTabs', $what)) {
            $iblockAdminTabs = $helper->AdminIblock()->exportElementForm($iblockId);
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/IblockExport.php',
            array(
                'iblockExport' => $iblockExport,
                'iblock' => $iblock,
                'iblockType' => $iblockType,
                'iblockFields' => $iblockFields,
                'iblockProperties' => $iblockProperties,
                'iblockAdminTabs' => $iblockAdminTabs,
            )
        );
    }


    /**
     * Структура инфоблоков для построения выпадающего списка
     * @return array
     */
    public function getIblocksStructure() {

        $structure = [];
        $iblockHelper = new IblockHelper();

        $iblockTypes = $iblockHelper->getIblockTypes();

        foreach ($iblockTypes as $iblockType) {
            $structure[$iblockType['ID']] = [
                'title' => '[' . $iblockType['ID'] . '] ' . $iblockType['LANG'][LANGUAGE_ID]['NAME'],
                'items' => array(),
            ];
        }

        $iblocks = $iblockHelper->getIblocks();
        foreach ($iblocks as $iblock) {
            $structure[$iblock['IBLOCK_TYPE_ID']]['items'][] = [
                'title' => '[' . $iblock['CODE'] . '] ' . $iblock['NAME'],
                'value' => $iblock['ID']
            ];
        }

        return $structure;
    }


    protected function getPropsStructure($props = array()) {
        $structure = [
            0 => ['items' => []]
        ];

        foreach ($props as $prop) {
            $structure[0]['items'][] = [
                'title' => '[' . $prop['CODE'] . '] ' . $prop['NAME'],
                'value' => $prop['ID']
            ];
        }

        return $structure;
    }

}