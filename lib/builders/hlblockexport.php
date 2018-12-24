<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Exceptions\HelperException;

class HlblockExport extends VersionBuilder
{

    protected function isBuilderEnabled() {
        return (\CModule::IncludeModule('highloadblock'));
    }

    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport2'));

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

        $this->addField('hlblock_id', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport_HlblockId'),
            'placeholder' => '',
            'multiple' => 1,
            'value' => array(),
            'width' => 250,
            'items' => $this->getHlStructure()
        ));

        $hlblockIds = $this->getFieldValue('hlblock_id');
        if (!empty($hlblockIds)) {
            $hlblockIds = is_array($hlblockIds) ? $hlblockIds : array($hlblockIds);
        } else {
            $this->rebuildField('hlblock_id');
        }

        $items = array();
        foreach ($hlblockIds as $hlblockId) {
            $hlblock = $helper->Hlblock()->getHlblock($hlblockId);
            if (!empty($hlblock['ID'])) {

                $hlblockEntities = $helper->UserTypeEntity()->exportUserTypeEntities('HLBLOCK_' . $hlblock['ID']);
                unset($hlblock['ID']);

                $items[] = array(
                    'hlblock' => $hlblock,
                    'hlblockEntities' => $hlblockEntities
                );
            }
        }


        $this->createVersionFile(
            Module::getModuleDir() . '/templates/HlblockExport.php', array(
            'items' => $items,
        ));

    }

    protected function getHlStructure() {
        $helper = HelperManager::getInstance();

        $hlblocks = $helper->Hlblock()->getHlblocks();

        $structure = [
            0 => ['items' => []]
        ];

        foreach ($hlblocks as $hlblock) {
            $structure[0]['items'][] = [
                'title' => $hlblock['NAME'],
                'value' => $hlblock['ID']
            ];
        }

        return $structure;

    }
}