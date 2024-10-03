<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockDeleteBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Iblock()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_IblockDelete'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Iblock'));

        $this->addVersionFields();
    }

    protected function execute()
    {
        $helper = $this->getHelperManager();

        $iblockTypes = $helper->IblockExchange()->getIblockTypes();

        $iblocks = $helper->IblockExchange()->getIblocks();

        $itemsForSelect = $helper->IblockExchange()->createIblocksStructure(
            $iblockTypes,
            $iblocks
        );

        $iblockIds = $this->addFieldAndReturn(
            'iblock_ids', [
                'title'       => Locale::getMessage('BUILDER_IblockExport_IblockId'),
                'placeholder' => '',
                'width'       => 250,
                'items'       => $itemsForSelect,
                'multiple'    => 1,
                'value'       => [],
            ]
        );

        if (empty($iblockIds)) {
            $this->rebuildField('iblock_ids');
        }

        $selectedIblocks = [];
        foreach ($iblocks as $iblock) {
            if (in_array($iblock['ID'], $iblockIds)) {
                $selectedIblocks[] = $iblock;
            }
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/IblockDelete.php',
            [
                'iblocks' => $selectedIblocks,
            ],
            false
        );
    }
}
