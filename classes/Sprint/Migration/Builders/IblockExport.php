<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Exceptions\HelperException;

class IblockExport extends AbstractBuilder
{

    public function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport2'));
        $this->setTemplateFile(Module::getModuleDir() . '/templates/IblockExport.php');

        $this->setField('prefix', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_PREFIX'),
            'value' => $this->getConfigVal('version_prefix'),
            'width' => 250,
        ));

        $this->setField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ));

        $this->setField('iblock_id', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_IblockId'),
            'placeholder' => 'ID'
        ));
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

        $this->setTemplateVar('iblock', $iblock);
        $this->setTemplateVar('iblockType', $iblockType);
        $this->setTemplateVar('iblockFields', $iblockFields);
        $this->setTemplateVar('iblockProperties', $iblockProperties);
        $this->setTemplateVar('iblockAdminTabs', $iblockAdminTabs);
    }
}