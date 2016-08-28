<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;

use Sprint\Migration\Helpers\IblockHelper;
use Sprint\Migration\Helpers\AdminIblockHelper;

use Sprint\Migration\Helpers\AgentHelper;
use Sprint\Migration\Helpers\EventHelper;
use Sprint\Migration\Helpers\LangHelper;
use Sprint\Migration\Helpers\SiteHelper;

use Sprint\Migration\Helpers\UserTypeEntityHelper;
use Sprint\Migration\Helpers\UserGroupHelper;

/**
 * @method IblockHelper             iblock()
 * @method AdminIblockHelper        adminIblock()
 *
 * @method AgentHelper              agent()
 * @method EventHelper              event()
 * @method LangHelper               lang()
 * @method SiteHelper               site()
 *
 * @method UserTypeEntityHelper     userTypeEntity()
 * @method UserGroupHelper          userGroup()
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

        $helperClass = '\\Sprint\\Migration\\Helpers\\' . ucfirst($name) . 'Helper';
        if (!class_exists($helperClass)){
            Throw new HelperException("Helper $name not found");
        }

        $this->cache[$name] = new $helperClass;
        return $this->cache[$name];
    }
}