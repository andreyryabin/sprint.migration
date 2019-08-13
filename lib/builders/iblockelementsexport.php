<?php

namespace Sprint\Migration\Builders;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class IblockElementsExport extends VersionBuilder
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

        $versionName = $this->getVersionName();

        $resourceDir = $this->createVersionResourcesDir($versionName);

        $exchange = new \Sprint\Migration\Exchange\IblockExport($this);
        $exchange->from($iblockId);
        $exchange->to($resourceDir . '/iblock_elements.xml');
        $exchange->execute();

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/IblockElementsExport.php',
            [
                'version' => $versionName,
                'iblock' => $iblock,
            ]
        );
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