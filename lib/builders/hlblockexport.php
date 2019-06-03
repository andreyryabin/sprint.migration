<?php

namespace Sprint\Migration\Builders;

use Bitrix\Main\Loader;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class HlblockExport extends VersionBuilder
{

    protected function isBuilderEnabled()
    {
        return (Loader::includeModule('highloadblock'));
    }

    protected function initialize()
    {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport2'));

        $this->addVersionFields();
    }


    protected function execute()
    {
        $helper = HelperManager::getInstance();

        $this->addField('hlblock_id', [
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport_HlblockId'),
            'placeholder' => '',
            'multiple' => 1,
            'value' => [],
            'width' => 250,
            'items' => $this->getHlStructure(),
        ]);

        $hlblockIds = $this->getFieldValue('hlblock_id');
        if (!empty($hlblockIds)) {
            $hlblockIds = is_array($hlblockIds) ? $hlblockIds : [$hlblockIds];
        } else {
            $this->rebuildField('hlblock_id');
        }

        $items = [];
        foreach ($hlblockIds as $hlblockId) {
            $hlblock = $helper->Hlblock()->getHlblock($hlblockId);
            if (!empty($hlblock['ID'])) {

                $hlblockEntities = $helper->UserTypeEntity()->exportUserTypeEntities('HLBLOCK_' . $hlblock['ID']);
                unset($hlblock['ID']);

                $items[] = [
                    'hlblock' => $hlblock,
                    'hlblockEntities' => $hlblockEntities,
                ];
            }
        }


        $this->createVersionFile(
            Module::getModuleDir() . '/templates/HlblockExport.php', [
            'items' => $items,
        ]);

    }

    protected function getHlStructure()
    {
        $helper = HelperManager::getInstance();

        $hlblocks = $helper->Hlblock()->getHlblocks();

        $structure = [
            0 => ['items' => []],
        ];

        foreach ($hlblocks as $hlblock) {
            $structure[0]['items'][] = [
                'title' => $hlblock['NAME'],
                'value' => $hlblock['ID'],
            ];
        }

        return $structure;

    }
}