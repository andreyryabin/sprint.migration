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


    public function execute() {
        $helper = new HelperManager();

        $iblockId = $this->getFieldValue('iblock_id');
        $this->exitIfEmpty($iblockId, 'Iblock not found');

        $iblock = $helper->Iblock()->getIblock(array('ID' => $iblockId));
        $this->exitIfEmpty($iblock, 'Iblock not found');

        $iblockType = $helper->Iblock()->getIblockType($iblock['IBLOCK_TYPE_ID']);

        $iblockProperties = $helper->Iblock()->getProperties($iblock['ID']);
        foreach ($iblockProperties as $index => $iblockProperty) {
            unset($iblockProperty['ID']);
            unset($iblockProperty['IBLOCK_ID']);
            unset($iblockProperty['TIMESTAMP_X']);
            $iblockProperties[$index] = $iblockProperty;
        }

        $allFields = $helper->Iblock()->getIblockFields($iblock['ID']);
        $iblockFields = array();
        foreach ($allFields as $fieldId => $iblockField) {
            if ($iblockField["VISIBLE"] == "N" || preg_match("/^(SECTION_|LOG_)/", $fieldId)) {
                continue;
            }

            $iblockFields[$fieldId] = $iblockField;
        }


        try {
            $iblockAdminTabs = $helper->AdminIblock()->extractElementForm($iblock['ID']);
        } catch (HelperException $e) {
            $iblockAdminTabs = !empty($iblockAdminTabs) ? $iblockAdminTabs : array();
        }

        unset($iblock['ID']);
        unset($iblock['TIMESTAMP_X']);
        unset($iblock['TMP_ID']);

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/IblockExport.php', array(
            'iblock' => $iblock,
            'iblockType' => $iblockType,
            'iblockFields' => $iblockFields,
            'iblockProperties' => $iblockProperties,
            'iblockAdminTabs' => $iblockAdminTabs,
        ));
    }
}