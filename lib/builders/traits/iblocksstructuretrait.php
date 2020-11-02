<?php

namespace Sprint\Migration\Builders\Traits;

use Sprint\Migration\HelperManager;

/**
 * Trait IblocksStructureTrait
 *
 * @package Sprint\Migration\Builders\Traits
 *
 * @method HelperManager getHelperManager()
 */
trait IblocksStructureTrait
{
    /**
     * Структура инфоблоков для построения выпадающего списка
     *
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
     *
     * @return array
     */
    protected function getIblockPropertiesStructure($iblockId)
    {
        $helper = $this->getHelperManager();
        $props = $helper->Iblock()->exportProperties($iblockId);

        $res = [];
        foreach ($props as $prop) {
            $res[] = [
                'title' => '[' . $prop['CODE'] . '] ' . $prop['NAME'],
                'value' => $prop['CODE'],
            ];
        }
        return $res;
    }

    /**
     * @param $iblockId
     *
     * @return array
     */
    protected function getIblockElementFieldsStructure($iblockId)
    {
        $helper = $this->getHelperManager();
        $fields = $helper->Iblock()->exportIblockElementFields($iblockId);

        $res = [];
        foreach ($fields as $fieldName => $field) {
            $res[] = [
                'title' => '[' . $fieldName . '] ' . $field['NAME'],
                'value' => $fieldName,
            ];
        }
        return $res;
    }
}
