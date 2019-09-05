<?php

namespace Sprint\Migration\Builders\Traits;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\HelperManager;

/**
 * Trait HlblocksStructureTrait
 * @package Sprint\Migration\Builders\Traits
 *
 * @method HelperManager getHelperManager()
 */
trait HlblocksStructureTrait
{

    /**
     * @throws HelperException
     * @return array
     */
    protected function getHlblocksStructure()
    {
        $res = [];
        $helper = $this->getHelperManager();
        $hlblocks = $helper->Hlblock()->getHlblocks();
        foreach ($hlblocks as $hlblock) {
            $res[] = [
                'title' => $hlblock['NAME'],
                'value' => $hlblock['ID'],
            ];
        }
        return $res;
    }

    /**
     * @param $hlblockName
     * @throws HelperException
     * @return array
     */
    protected function getHlblockFieldsCodes($hlblockName)
    {
        $res = [];
        $helper = $this->getHelperManager();
        $items = $helper->Hlblock()->getFields($hlblockName);
        foreach ($items as $item) {
            if (!empty($item['FIELD_NAME'])) {
                $res[] = $item['FIELD_NAME'];
            }
        }
        return $res;
    }
}