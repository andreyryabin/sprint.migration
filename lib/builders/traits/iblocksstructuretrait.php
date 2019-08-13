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


}