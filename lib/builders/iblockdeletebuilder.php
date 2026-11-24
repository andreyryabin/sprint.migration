<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockDeleteBuilder extends VersionBuilder
{
    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Iblock()->isEnabled();
    }

    protected function initialize(): void
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
    protected function execute(): void
    {
        $exhelper = $this->getExchangeHelpers();

        $iblockIds = $this->askField('iblock_ids',
            (new CheckboxListField())
                ->setTitle(Locale::getMessage('BUILDER_IblockExport_IblockId'))
                ->setOptions($exhelper->IblockExchange()->getIblocksSelect(), 'ID', 'TITLE', 'GROUP'),
        );

        $selectedIblocks = [];
        foreach ($iblockIds as $iblockId) {
            $selectedIblocks[] = $exhelper->IblockExchange()->exportIblock($iblockId);
        }

        $this->confirmCreateVersion();

        $this->createVersionFile(
            Module::getModuleTemplateFile('IblockDelete'),
            [
                'iblocks' => $selectedIblocks,
            ],
            false,
        );
    }
}
