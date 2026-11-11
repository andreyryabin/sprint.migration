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

        $iblockIds = $this->addFieldAndReturn('iblock_ids', [
            'title'    => Locale::getMessage('BUILDER_IblockExport_IblockId'),
            'items'    => $helper->IblockExchange()->getIblocksStructure(),
            'multiple' => 1,
        ]);

        if (empty($iblockIds)) {
            $this->rebuildField('iblock_ids');
        }

        $selectedIblocks = [];
        foreach ($iblockIds as $iblockId) {
            $selectedIblocks[] = $helper->IblockExchange()->exportIblock($iblockId);
        }

        $this->createVersionFile(
            Module::getModuleTemplateFile('IblockDelete'),
            [
                'iblocks' => $selectedIblocks,
            ],
            false
        );
    }
}
