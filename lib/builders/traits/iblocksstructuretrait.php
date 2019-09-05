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
        $res = [];
        $helper = $this->getHelperManager();
        $iblockTypes = $helper->Iblock()->getIblockTypes();
        foreach ($iblockTypes as $iblockType) {
            $res[$iblockType['ID']] = [
                'title' => '[' . $iblockType['ID'] . '] ' . $iblockType['LANG'][LANGUAGE_ID]['NAME'],
                'items' => [],
            ];
        }

        $iblocks = $helper->Iblock()->getIblocks();
        foreach ($iblocks as $iblock) {
            $res[$iblock['IBLOCK_TYPE_ID']]['items'][] = [
                'title' => '[' . $iblock['CODE'] . '] ' . $iblock['NAME'],
                'value' => $iblock['ID'],
            ];
        }

        return $res;
    }

    /**
     * @param $iblockId
     * @return array
     */
    protected function getIblockPropertiesStructure($iblockId)
    {
        $res = [];
        $helper = $this->getHelperManager();
        $props = $helper->Iblock()->getProperties($iblockId);
        foreach ($props as $prop) {
            $res[] = [
                'title' => '[' . $prop['CODE'] . '] ' . $prop['NAME'],
                'value' => $prop['ID'],
            ];
        }
        return $res;
    }

    /**
     * @param $iblockId
     * @return array
     */
    protected function getIblockPropertiesCodes($iblockId)
    {
        $res = [];
        $helper = $this->getHelperManager();
        $props = $helper->Iblock()->getProperties($iblockId);
        foreach ($props as $prop) {
            if (!empty($prop['CODE'])) {
                $res[] = $prop['CODE'];
            }
        }
        return $res;
    }
}