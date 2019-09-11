<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Builders\Traits\HlblocksStructureTrait;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class HlblockElementsBuilder extends VersionBuilder
{
    use HlblocksStructureTrait;

    /**
     * @return bool
     */
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Hlblock()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_HlblockElementsExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_HlblockElementsExport2'));
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
        $this->addField('hlblock_id', [
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_HlblockElementsExport_HlblockId'),
            'placeholder' => '',
            'width' => 250,
            'select' => $this->getHlblocksStructure(),
        ]);

        $hlblockId = $this->getFieldValue('hlblock_id');
        if (empty($hlblockId)) {
            $this->rebuildField('hlblock_id');
        }

        if (!isset($this->params['~version_name'])) {
            $this->params['~version_name'] = $this->getVersionName();
        }

        $versionName = $this->params['~version_name'];

        $this->getExchangeManager()
            ->HlblockElementsExport()
            ->setLimit(20)
            ->setExportFields(
                $this->getHlblockFieldsCodes($hlblockId)
            )
            ->setHlblockId($hlblockId)
            ->setExchangeFile(
                $this->getVersionResource($versionName, 'hlblock_elements.xml')
            )
            ->execute();

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/HlblockElementsExport.php',
            [
                'version' => $versionName,
            ]
        );

        unset($this->params['~version_name']);
    }
}