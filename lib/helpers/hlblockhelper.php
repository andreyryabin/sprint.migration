<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;
use Sprint\Migration\Helpers\Traits\Hlblock\HlblockElementTrait;
use Sprint\Migration\Helpers\Traits\Hlblock\HlblockGroupTrait;
use Sprint\Migration\Helpers\Traits\Hlblock\HlblockTrait;

class HlblockHelper extends Helper
{
    use HlblockTrait;
    use HlblockGroupTrait;
    use HlblockElementTrait;

    public function isEnabled(): bool
    {
        return $this->checkModules(['highloadblock']);
    }
}
