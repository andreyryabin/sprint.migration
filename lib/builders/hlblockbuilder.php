<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Builders\Traits\HlblocksStructureTrait;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class HlblockBuilder extends VersionBuilder
{
    use HlblocksStructureTrait;

    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Hlblock()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_HlblockExport1'));
        $this->setDescription(Locale::getMessage('BUILDER_HlblockExport2'));
        $this->addVersionFields();
    }

    /**
     * @throws HelperException
     * @throws RebuildException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $this->addField('hlblock_id', [
            'title' => Locale::getMessage('BUILDER_HlblockExport_HlblockId'),
            'placeholder' => '',
            'width' => 250,
            'select' => $this->getHlblocksStructure(),
        ]);

        $hlblockId = $this->getFieldValue('hlblock_id');
        if (empty($hlblockId)) {
            $this->rebuildField('hlblock_id');
        }

        $hlblock = $helper->Hlblock()->exportHlblock($hlblockId);
        if (empty($hlblockId)) {
            $this->rebuildField('hlblock_id');
        }

        $hlblockFields = $helper->Hlblock()->exportFields($hlblockId);

        $hlblockPermissions = $helper->Hlblock()->exportGroupPermissions($hlblockId);

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/HlblockExport.php',
            [
                'hlblock' => $hlblock,
                'hlblockFields' => $hlblockFields,
                'hlblockPermissions' => $hlblockPermissions,
            ]
        );

    }
}