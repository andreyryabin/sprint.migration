<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\VersionBuilder;

class IblockExport extends VersionBuilder
{

    public function initialize() {
        $this->addField('iblock_id', array(
            'title' => 'change iblock',
            'options' => array(
                33 => 'x33',
                44 => 'x44',
            )
        ));

        $this->addField('param1', array());
        $this->addField('param2', array());
    }


    public function execute(){

        $val = $this->getFieldValue('iblock_id');

        if ($val != 55){

            return false;

        }



    }
}