<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
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

    /**
     * @throws RebuildException
     * @throws HelperException
     * @throws MigrationException
     */
    protected function execute()
    {
        $exhelper = $this->getExchangeHelpers()->IblockExchange();

        $iblockIds = $this->addFieldAndReturn('iblock_ids', [
            'title'    => Locale::getMessage('BUILDER_IblockExport_IblockId'),
            'items'    => $exhelper->getIblocksStructure(),
            'multiple' => 1,
        ]);

        if (empty($iblockIds)) {
            $this->rebuildField('iblock_ids');
        }

        $selectedIblocks = [];
        foreach ($iblockIds as $iblockId) {
            $selectedIblocks[] = $exhelper->exportIblock($iblockId);
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
