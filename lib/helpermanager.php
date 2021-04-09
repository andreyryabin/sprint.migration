<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helpers\AdminIblockHelper;
use Sprint\Migration\Helpers\AgentHelper;
use Sprint\Migration\helpers\DeliveryServiceHelper;
use Sprint\Migration\Helpers\EventHelper;
use Sprint\Migration\Helpers\FormHelper;
use Sprint\Migration\Helpers\HlblockExchangeHelper;
use Sprint\Migration\Helpers\HlblockHelper;
use Sprint\Migration\Helpers\IblockExchangeHelper;
use Sprint\Migration\Helpers\IblockHelper;
use Sprint\Migration\Helpers\LangHelper;
use Sprint\Migration\Helpers\MedialibExchangeHelper;
use Sprint\Migration\Helpers\MedialibHelper;
use Sprint\Migration\Helpers\OptionHelper;
use Sprint\Migration\Helpers\SiteHelper;
use Sprint\Migration\Helpers\SqlHelper;
use Sprint\Migration\Helpers\UserGroupHelper;
use Sprint\Migration\Helpers\UserOptionsHelper;
use Sprint\Migration\Helpers\UserTypeEntityHelper;

/**
 * @method IblockHelper             Iblock()
 * @method HlblockHelper            Hlblock()
 * @method AgentHelper              Agent()
 * @method EventHelper              Event()
 * @method LangHelper               Lang()
 * @method SiteHelper               Site()
 * @method UserOptionsHelper        UserOptions()
 * @method UserTypeEntityHelper     UserTypeEntity()
 * @method UserGroupHelper          UserGroup()
 * @method OptionHelper             Option()
 * @method FormHelper               Form()
 * @method DeliveryServiceHelper    DeliveryService()
 * @method SqlHelper                Sql()
 * @method MedialibHelper           Medialib()
 * @method MedialibExchangeHelper   MedialibExchange()
 * @method IblockExchangeHelper     IblockExchange()
 * @method HlblockExchangeHelper    HlblockExchange()
 * @method AdminIblockHelper        AdminIblock()
 */
class HelperManager
{
    private        $cache      = [];
    private static $instance   = null;
    private        $registered = [];

    /**
     * @return HelperManager
     */
    public static function getInstance()
    {
        if (!isset(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @throws HelperException
     * @return Helper
     */
    public function __call($name, $arguments)
    {
        return $this->callHelper($name);
    }

    public function registerHelper($name, $class)
    {
        $this->registered[$name] = $class;
    }

    /**
     * @param $name
     *
     * @throws HelperException
     * @return Helper
     */
    protected function callHelper($name)
    {
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

        throw new HelperException("Helper $name not found");
    }
}
