<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Builders\Traits\HlblocksStructureTrait;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RebuildException;
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
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport2'));

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
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport_HlblockId'),
            'placeholder' => '',
            'multiple' => 1,
            'value' => [],
            'width' => 250,
            'select' => $this->getHlblocksStructure(),
        ]);

        $hlblockIds = $this->getFieldValue('hlblock_id');
        if (!empty($hlblockIds)) {
            $hlblockIds = is_array($hlblockIds) ? $hlblockIds : [$hlblockIds];
        } else {
            $this->rebuildField('hlblock_id');
        }

        $items = [];
        foreach ($hlblockIds as $hlblockId) {
            $hlblock = $helper->Hlblock()->getHlblock($hlblockId);
            if (!empty($hlblock['ID'])) {

                $hlblockEntities = $helper->UserTypeEntity()->exportUserTypeEntities('HLBLOCK_' . $hlblock['ID']);
                unset($hlblock['ID']);

                $items[] = [
                    'hlblock' => $hlblock,
                    'hlblockEntities' => $hlblockEntities,
                ];
            }
        }


        $this->createVersionFile(
            Module::getModuleDir() . '/templates/HlblockExport.php', [
            'items' => $items,
        ]);

    }
}