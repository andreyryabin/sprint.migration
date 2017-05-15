<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;

class IblockExport extends VersionBuilder
{

    public function initialize() {


        $this->setField('iblock_id', array(
            'title' => 'Iblock Id'
        ));
    }


    public function execute(){
        $helper = new HelperManager();

        $iblock = $helper->Iblock()->getIblock(array(
            'ID' => $this->getFieldValue('iblock_id')
        ));

        $this->exitIfEmpty($iblock, 'Iblock not found');

        $iblockFields = $helper->Iblock()->getIblockFields($iblock['ID']);



        unset($iblock['ID']);
        unset($iblock['TIMESTAMP_X']);

        $this->setTemplateVar('iblock', $iblock);
        $this->setTemplateVar('iblockFields', $iblockFields);

        //$this->exitIf(1,1);

        $this->setTemplateName('IblockExport');


    }
}