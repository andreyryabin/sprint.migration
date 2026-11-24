<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\RadioListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockCategoryBuilder extends VersionBuilder
{

    protected function isBuilderEnabled(): bool
    {
        return $this->getHelperManager()->Iblock()->isEnabled();
    }

    protected function initialize(): void
    {
        $this->setTitle(Locale::getMessage('BUILDER_IblockCategoryExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_IblockCategoryExport2'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Iblock'));

        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws RebuildException
     * @throws MigrationException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();
        $exhelper = $this->getExchangeHelpers();

        $iblockId = (int) $this->askField('iblock_id',
            (new RadioListField(Locale::getMessage('BUILDER_IblockCategoryExport_IblockId')))
                ->setOptions($exhelper->IblockExchange()->getIblocksSelect(), 'ID', 'TITLE', 'GROUP'),
        );

        $iblock = $helper->Iblock()->exportIblock($iblockId);
        if (empty($iblock)) {
            $this->resetField('iblock_id');
        }

        $sectionTree = $helper->Iblock()->exportSectionsTree($iblockId);

        $this->confirmCreateVersion();

        $this->createVersionFile(
            Module::getModuleTemplateFile('IblockCategoryExport'),
            [
                'iblock'      => $iblock,
                'sectionTree' => $sectionTree,
            ],
        );
    }
}
