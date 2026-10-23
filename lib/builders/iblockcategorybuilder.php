<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\RestartableWriter;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockCategoryBuilder extends VersionBuilder
{
    /**
     * @return bool
     */
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Iblock()->isEnabled();
    }

    protected function initialize()
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
     * @throws RestartException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();
        $exhelper = $this->getHelperManager()->IblockSectionExchange();

        $iblockId = $this->addFieldAndReturn(
            'iblock_id',
            [
                'title'       => Locale::getMessage('BUILDER_IblockCategoryExport_IblockId'),
                'placeholder' => '',
                'width'       => 250,
                'items'       => $this->getHelperManager()->IblockExchange()->getIblocksStructure(),
            ]
        );

        $iblock = $helper->Iblock()->exportIblock($iblockId);
        if (empty($iblock)) {
            $this->rebuildField('iblock_id');
        }

        (new RestartableWriter($this, $this->getVersionExchangeDir()))
            ->setExchangeResource('iblock_sections.xml')
            ->execute(
                attributesFn: fn() => $exhelper->getWriterAttributes($iblockId),
                totalCountFn: fn() => $exhelper->getWriterRecordsCount($iblockId),
                recordsFn: fn($offset, $limit) => $exhelper->getWriterRecordsTag(
                    $offset,
                    $limit,
                    $iblockId
                ),
                progressFn: fn($value, $totalCount) => $this->outProgress(
                    'Progress: ',
                    $value,
                    $totalCount
                )
            );

        $this->createVersionFile(Module::getModuleTemplateFile('IblockCategoryExport'));
    }
}
