<?php

namespace Sprint\Migration\Builders\Traits;

use Sprint\Migration\HelperManager;

/**
 * Trait IblocksStructureTrait
 * @package Sprint\Migration\Builders\Traits
 *
 * @method HelperManager getHelperManager()
 */
trait IblocksStructureTrait
{

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

    /**
     * @param $iblockId
     * @return array
     */
    protected function getPropsStructure($iblockId)
    {
        $helper = $this->getHelperManager();
        $props = $helper->Iblock()->getProperties($iblockId);

        $structure = [
            0 => ['items' => []],
        ];

        foreach ($props as $prop) {
            $structure[0]['items'][] = [
                'title' => '[' . $prop['CODE'] . '] ' . $prop['NAME'],
                'value' => $prop['ID'],
            ];
        }

        return $structure;
    }

    /**
     * @param $iblockId
     * @return array
     */
    protected function getPropsCodes($iblockId)
    {
        $helper = $this->getHelperManager();
        $props = $helper->Iblock()->getProperties($iblockId);
        $res = [];
        foreach ($props as $prop) {
            if (!empty($prop['CODE'])) {
                $res[] = $prop['CODE'];
            }
        }
        return $res;

    }
}