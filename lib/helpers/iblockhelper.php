<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;
use Sprint\Migration\Helpers\Traits\Iblock\IblockElementTrait;
use Sprint\Migration\Helpers\Traits\Iblock\IblockFieldTrait;
use Sprint\Migration\Helpers\Traits\Iblock\IblockPermissionsTrait;
use Sprint\Migration\Helpers\Traits\Iblock\IblockPropertyTrait;
use Sprint\Migration\Helpers\Traits\Iblock\IblockSectionTrait;
use Sprint\Migration\Helpers\Traits\Iblock\IblockTrait;
use Sprint\Migration\Helpers\Traits\Iblock\IblockTypeTrait;

class IblockHelper extends Helper
{
    use IblockPropertyTrait;
    use IblockFieldTrait;
    use IblockElementTrait;
    use IblockSectionTrait;
    use IblockTypeTrait;
    use IblockTrait;
    use IblockPermissionsTrait;

    /**
     * IblockHelper constructor.
     */
    public function isEnabled(): bool
    {
        return $this->checkModules(['iblock']);
    }
}
