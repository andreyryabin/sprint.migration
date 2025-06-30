<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Highloadblock\HighloadBlockRightsTable;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Helpers\Traits\Hlblock\HlblockElementTrait;
use Sprint\Migration\Helpers\Traits\Hlblock\HlblockGroupTrait;
use Sprint\Migration\Helpers\Traits\Hlblock\HlblockPermissionsTrait;
use Sprint\Migration\Helpers\Traits\Hlblock\HlblockTrait;

class HlblockHelper extends Helper
{
    use HlblockTrait;
    use HlblockElementTrait;
    use HlblockPermissionsTrait;
    use HlblockGroupTrait;

    public function isEnabled(): bool
    {
        return $this->checkModules(['highloadblock']);
    }

    /**
     * @throws HelperException
     */
    public function getHlblockRights(int $hlblockId): array
    {
        $this->checkHlblockRightsTable();

        try {
            return HighloadBlockRightsTable::getList(['filter' => ['HL_ID' => $hlblockId]])->fetchAll();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function checkHlblockRightsTable(): bool
    {
        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockRightsTable')) {
            throw new HelperException('HighloadBlockRightsTable not installed');
        }
        return true;
    }
}
