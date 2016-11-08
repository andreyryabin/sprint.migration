<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

use Sprint\Migration\Helpers\IblockHelper;
use Sprint\Migration\Helpers\HlblockHelper;
use Sprint\Migration\Helpers\AdminIblockHelper;

use Sprint\Migration\Helpers\AgentHelper;
use Sprint\Migration\Helpers\EventHelper;
use Sprint\Migration\Helpers\LangHelper;
use Sprint\Migration\Helpers\SiteHelper;

use Sprint\Migration\Helpers\UserTypeEntityHelper;
use Sprint\Migration\Helpers\UserGroupHelper;

/**
 * @method IblockHelper             Iblock()
 * @method HlblockHelper            Hlblock()
 * @method AdminIblockHelper        AdminIblock()
 * @method AgentHelper              Agent()
 * @method EventHelper              Event()
 * @method LangHelper               Lang()
 * @method SiteHelper               Site()
 * @method UserTypeEntityHelper     UserTypeEntity()
 * @method UserGroupHelper          UserGroup()
 */

class HelperManager
{

    protected $cache = array();

    public function __call($name, $arguments) {
        return $this->callHelper($name);
    }

    protected function callHelper($name){
        if (isset($this->cache[$name])){
            return $this->cache[$name];
        }

        $helperClass = '\\Sprint\\Migration\\Helpers\\' . $name . 'Helper';
        if (!class_exists($helperClass)){
            Throw new HelperException("Helper $name not found");
        }

        $this->cache[$name] = new $helperClass;
        return $this->cache[$name];
    }
}