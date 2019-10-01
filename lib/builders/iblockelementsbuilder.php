<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Builders\Traits\IblocksStructureTrait;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockElementsBuilder extends VersionBuilder
{
    use IblocksStructureTrait;

    /**
     * @return bool
     */
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Iblock()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_IblockElementsExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_IblockElementsExport2'));

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
            'title' => Locale::getMessage('BUILDER_IblockElementsExport_IblockId'),
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

        if (!isset($this->params['~version_name'])) {
            $this->params['~version_name'] = $this->getVersionName();
        }

        $versionName = $this->params['~version_name'];

        $this->getExchangeManager()
            ->IblockElementsExport()
            ->setExportFields([
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
            ])
            ->setExportProperties(
                $this->getIblockPropertiesCodes($iblockId)
            )
            ->setIblockId($iblockId)
            ->setLimit(20)
            ->setExchangeFile(
                $this->getVersionResourceFile($versionName, 'iblock_elements.xml')
            )->execute();

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/IblockElementsExport.php',
            [
                'version' => $versionName,
            ]
        );

        unset($this->params['~version_name']);
    }


}