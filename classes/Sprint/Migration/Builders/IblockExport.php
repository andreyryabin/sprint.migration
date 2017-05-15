<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\HelperManager;

class IblockExport extends AbstractBuilder
{

    public function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport'));
        $this->setTemplateFile(Module::getModuleDir() . '/templates/IblockExport.php');

        $this->setField('iblock_id', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_IblockExport_IblockId'),
            'placeholder' => 'ID'
        ));
    }


    public function execute(){
        $helper = new HelperManager();

        $iblockId = $this->getFieldValue('iblock_id');
        $this->exitIfEmpty($iblockId, 'Iblock not found');

        $iblock = $helper->Iblock()->getIblock(array('ID' => $iblockId));
        $this->exitIfEmpty($iblock, 'Iblock not found');

        $iblockFields = $helper->Iblock()->getIblockFields($iblock['ID']);



        unset($iblock['ID']);
        unset($iblock['TIMESTAMP_X']);

        $this->setTemplateVar('iblock', $iblock);
        $this->setTemplateVar('iblockFields', $iblockFields);

        //$this->exitIf(1,1);




    }
}