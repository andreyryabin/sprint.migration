<?php

namespace Sprint\Migration\Builders;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\IblockElementsExport;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockElementsBuilder extends VersionBuilder
{

    /**
     * @throws LoaderException
     * @return bool
     */
    protected function isBuilderEnabled()
    {
        return (Loader::includeModule('iblock'));
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

        $exchange = new IblockElementsExport($this);
        $exchange->from($iblockId);
        $exchange->to($file);
        $exchange->execute();

        $versionName = $this->createVersionFile(
            Module::getModuleDir() . '/templates/IblockElementsExport.php',
            [
                'iblock' => $iblock,
            ]
        );

        $resourceDir = $this->createVersionResourcesDir($versionName);
        rename($file, $resourceDir . '/iblock_elements.xml');
    }

    /**
     * Структура инфоблоков для построения выпадающего списка
     * @return array
     */
    public function getIblocksStructure()
    {
        $helper = $this->getHelperManager();
        $iblockTypes = $helper->Iblock()->getIblockTypes();

        $structure = [];
        foreach ($iblockTypes as $iblockType) {
            $structure[$iblockType['ID']] = [
                'title' => '[' . $iblockType['ID'] . '] ' . $iblockType['LANG'][LANGUAGE_ID]['NAME'],
                'items' => [],
            ];
        }

        $iblocks = $helper->Iblock()->getIblocks();
        foreach ($iblocks as $iblock) {
            $structure[$iblock['IBLOCK_TYPE_ID']]['items'][] = [
                'title' => '[' . $iblock['CODE'] . '] ' . $iblock['NAME'],
                'value' => $iblock['ID'],
            ];
        }

        return $structure;
    }
}