<?php

namespace Sprint\Migration\Builders;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Sprint\Migration\Builders\Traits\IblocksStructureTrait;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\IblockElementsExport;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class HlblockElementsBuilder extends VersionBuilder
{
    /**
     * @return bool
     */
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Hlblock()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_IblockElementsExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_IblockElementsExport2'));

        $this->addVersionFields();
    }

    /**
     * @throws RebuildException
     * @throws ExchangeException
     * @throws RestartException
     * @throws HelperException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $this->addField('iblock_id', [
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockElementsExport_IblockId'),
            'placeholder' => '',
            'width' => 250,
            'items' => $this->getIblocksStructure(),
        ]);

        $iblockId = $this->getFieldValue('iblock_id');
        if (empty($iblockId)) {
            $this->rebuildField('iblock_id');
        }

        $iblock = $helper->Iblock()->exportIblock($iblockId);
        if (empty($iblock)) {
            $this->rebuildField('iblock_id');
        }

        $file = Module::getDocRoot() . '/bitrix/tmp/sprint.migration/iblock_elements.xml';
        Module::createDir(dirname($file));

        $exchange = new IblockElementsExport($this);
        $exchange->setLimit(10);

        $exchange->setExportFields([
            'NAME',
            'CODE',
            'SORT',
            'XML_ID',
            'TAGS',
            'DATE_ACTIVE_FROM',
            'DATE_ACTIVE_TO',
            'PREVIEW_TEXT',
            'PREVIEW_TEXT_TYPE',
            'DETAIL_TEXT',
            'DETAIL_TEXT_TYPE',
            'PREVIEW_PICTURE',
            'DETAIL_PICTURE',
        ]);

        $exchange->setExportProperties(
            $this->getPropsCodes($iblockId)
        );

        $exchange->from($iblockId);
        $exchange->to($file);

        $exchange->execute();

        $versionName = $this->createVersionFile(
            Module::getModuleDir() . '/templates/IblockElementsExport.php',
            [
                'iblock' => $iblock,
            ]
        );

        $resourceDir = Module::createDir($this->getVersionResourcesDir($versionName));
        rename($file, $resourceDir . '/iblock_elements.xml');
    }


}