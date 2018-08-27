<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Helpers\IblockHelper;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Exceptions\HelperException;

class IblockExport extends VersionBuilder
{

    public function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport2'));

        $this->addField('iblock_id', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_IblockId'),
            'placeholder' => '',
            'width' => 250,
            'items' => $this->getIblocksStructure()
        ));

        $this->addField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ));

        $this->addField('what', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_What'),
            'width' => 250,
            'multiple' => 1,
            'value' => array(
                'iblockType',
                'iblock',
                'iblockFields',
                'iblockProperties',
                'iblockAdminTabs',
            ),
            'items' => [0 => [
                'items' => [
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
            ]]
        ));


    }

    public function execute() {
        $helper = new HelperManager();

        $iblockId = $this->getFieldValue('iblock_id');
        $this->exitIfEmpty($iblockId, 'Iblock not found');

        $iblock = $helper->Iblock()->getIblock(array('ID' => $iblockId));
        $this->exitIfEmpty($iblock, 'Iblock not found');

        unset($iblock['ID']);
        unset($iblock['TIMESTAMP_X']);
        unset($iblock['TMP_ID']);

        $what = $this->getFieldValue('what');
        if (!empty($what)) {
            $what = is_array($what) ? $what : array($what);
        } else {
            $this->rebuildField('what');
        }

        $iblockType = array();
        $iblockProperties = array();
        $iblockFields = array();
        $iblockAdminTabs = array();

        if (in_array('iblockType', $what)) {
            $iblockType = $helper->Iblock()->getIblockType($iblock['IBLOCK_TYPE_ID']);
        }

        if (in_array('iblockProperties', $what)) {

            $props = $helper->Iblock()->getProperties($iblockId);
            $this->addField('property_ids', array(
                'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_PropertyIds'),
                'width' => 250,
                'multiple' => 1,
                'value' => $this->getPropsValues($props),
                'items' => $this->getPropsStructure($props)
            ));

            $propertyIds = $this->getFieldValue('property_ids');
            if (!empty($propertyIds)) {
                $propertyIds = is_array($propertyIds) ? $propertyIds : array($propertyIds);
            } else {
                $this->rebuildField('property_ids');
            }

            $iblockProperties = array();
            foreach ($propertyIds as $propertyId) {
                $iblockProperty = $this->getPropById($propertyId, $props);
                if ($iblockProperty) {
                    unset($iblockProperty['ID']);
                    unset($iblockProperty['IBLOCK_ID']);
                    unset($iblockProperty['TIMESTAMP_X']);
                    $iblockProperties[] = $iblockProperty;
                }
            }
        }


        if (in_array('iblockFields', $what)) {
            $iblockFields = array();
            $allFields = $helper->Iblock()->getIblockFields($iblockId);
            foreach ($allFields as $fieldId => $iblockField) {
                if ($iblockField["VISIBLE"] == "N" || preg_match("/^(SECTION_|LOG_)/", $fieldId)) {
                    continue;
                }
                $iblockFields[$fieldId] = $iblockField;
            }
        }


        if (in_array('iblockAdminTabs', $what)) {
            try {
                $iblockAdminTabs = $helper->AdminIblock()->extractElementForm($iblockId);
            } catch (HelperException $e) {
                $iblockAdminTabs = !empty($iblockAdminTabs) ? $iblockAdminTabs : array();
            }
        }


        if (!in_array('iblock', $what)) {
            $iblock = array();
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/IblockExport.php',
            array(
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
                'title' => $iblockType['LANG'][LANGUAGE_ID]['NAME'],
                'items' => array(),
            ];
        }

        $iblocks = $iblockHelper->getIblocks();
        foreach ($iblocks as $iblock) {
            $structure[$iblock['IBLOCK_TYPE_ID']]['items'][] = [
                'title' => $iblock['NAME'],
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
                'title' => $prop['NAME'],
                'value' => $prop['ID']
            ];
        }

        return $structure;
    }

    protected function getPropById($propId, $props = array()) {
        foreach ($props as $prop) {
            if ($propId == $prop['ID']) {
                return $prop;
            }
        }
        return false;
    }

    protected function getPropsValues($props = array()) {
        $propsIds = array();
        foreach ($props as $prop) {
            $propsIds[] = $prop['ID'];
        }
        return $propsIds;
    }

}