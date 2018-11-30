<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\HelperManager;

class SchemaExport extends AbstractBuilder
{

    protected function isBuilderEnabled() {
        return true;
    }

    protected function initialize() {
        $this->setTitle('SchemaExport');
        $this->setGroup('schema');
    }


    protected function execute() {

        $helper = new HelperManager();

        $iblockId = $helper->Iblock()->getIblockIdIfExists('news_news');

        $exportIblock = $helper->Iblock()->exportIblock($iblockId);

        $exportProps = $helper->Iblock()->exportProperties($iblockId);

        $exportFields = $helper->Iblock()->exportIblockFields($iblockId);

        $schemaDir = $this->getVersionConfig()->getVal('migration_dir') . '/schema';

        if (!is_dir($schemaDir)) {
            mkdir($schemaDir, BX_DIR_PERMISSIONS, true);
        }

        file_put_contents($schemaDir . '/iblock.json',
            json_encode(array(
                'iblock' =>$exportIblock,
                'fields' =>$exportFields,
                'props' => $exportProps,


            ), JSON_UNESCAPED_UNICODE + JSON_PRETTY_PRINT)
        );


        $this->outSuccess('ok');
    }


}