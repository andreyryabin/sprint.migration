<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Exceptions\HelperException;

class HlblockExport extends AbstractBuilder
{

    public function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport2'));
        $this->setTemplateFile(Module::getModuleDir() . '/templates/HlblockExport.php');

        $this->setField('prefix', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_PREFIX'),
            'value' => $this->getConfigVal('version_prefix'),
            'width' => 250,
        ));

        $this->setField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ));

        $this->setField('hlblock_id', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_HlblockExport_HlblockId'),
            'placeholder' => 'ID|NAME'
        ));
    }


    public function execute() {
        $helper = new HelperManager();

        $hlblockId = $this->getFieldValue('hlblock_id');
        $this->exitIfEmpty($hlblockId, 'Hlblock not found');

        $hlblock = $helper->Hlblock()->getHlblock($hlblockId);
        $this->exitIfEmpty($hlblock, 'Hlblock not found');

        $hlblockEntities = $helper->UserTypeEntity()->getUserTypeEntities('HLBLOCK_'.$hlblock['ID']);
        foreach ($hlblockEntities as $index => $entity){
            unset($entity['ID']);
            unset($entity['ENTITY_ID']);
            $hlblockEntities[$index] = $entity;
        }

        unset($hlblock['ID']);
        $this->setTemplateVar('hlblock', $hlblock);
        $this->setTemplateVar('hlblockEntities', $hlblockEntities);
    }
}