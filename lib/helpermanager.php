<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helpers\AdminIblockHelper;
use Sprint\Migration\Helpers\AgentHelper;
use Sprint\Migration\helpers\DeliveryServiceHelper;
use Sprint\Migration\Helpers\EventHelper;
use Sprint\Migration\Helpers\FormHelper;
use Sprint\Migration\Helpers\HlblockHelper;
use Sprint\Migration\Helpers\IblockHelper;
use Sprint\Migration\Helpers\LangHelper;
use Sprint\Migration\Helpers\OptionHelper;
use Sprint\Migration\Helpers\SiteHelper;
use Sprint\Migration\Helpers\UserGroupHelper;
use Sprint\Migration\Helpers\UserTypeEntityHelper;

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
 * @method OptionHelper             Option()
 * @method FormHelper               Form()
 * @method DeliveryServiceHelper    DeliveryService()
 */
class HelperManager
{

    private $cache = array();

    private static $instance = null;

    private $registered = array();

    /**
     * @return HelperManager
     */
    public static function getInstance() {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function __call($name, $arguments) {
        return $this->callHelper($name);
    }

    public function registerHelper($name, $class) {
        $this->registered[$name] = $class;
    }

    /**
     * @param $name
     * @return Helper
     * @throws HelperException
     */
    protected function callHelper($name) {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $helperClass = '\\Sprint\\Migration\\Helpers\\' . $name . 'Helper';
        if (class_exists($helperClass)) {
            $this->cache[$name] = new $helperClass;
            return $this->cache[$name];
        }

        if (isset($this->registered[$name])) {
            $helperClass = $this->registered[$name];
            if (class_exists($helperClass)) {
                $this->cache[$name] = new $helperClass;
                return $this->cache[$name];
            }
        }

        Throw new HelperException("Helper $name not found");
    }
}
