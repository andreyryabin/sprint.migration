<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Exceptions\HelperException;

class OptionExport extends VersionBuilder
{

    protected function isBuilderEnabled() {
        $helper = HelperManager::getInstance();
        return $helper->Option()->isEnabled();
    }

    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_OptionExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_OptionExport2'));

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

        $this->addField('module_id', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_OptionExport_module_id'),
            'placeholder' => '',
            'multiple' => 1,
            'value' => array(),
            'width' => 250,
            'select' => $this->getModules()
        ));

        $moduleIds = $this->getFieldValue('module_id');
        if (empty($moduleIds)) {
            $this->rebuildField('module_id');
        }

        $moduleIds = is_array($moduleIds) ? $moduleIds : array($moduleIds);

        $items = array();
        foreach ($moduleIds as $moduleId) {
            $options = $helper->Option()->getOptions(array(
                'MODULE_ID' => $moduleId
            ));

            foreach ($options as $option){
                $items[] = $option;
            }
        }

        if (empty($items)) {
            $this->rebuildField('module_id');
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/OptionExport.php', array(
            'items' => $items,
        ));

    }

    protected function getModules() {
        $helper = HelperManager::getInstance();

        $items = $helper->Option()->getModules();

        $result = [];
        foreach ($items as $item) {
            $result[] = [
                'title' => $item['ID'],
                'value' => $item['ID']
            ];
            
        }

        return $result;

    }
}