<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;
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
 */
class HelperManager
{
    private static $instance   = null;
    private array  $registered = [];

    public static function getInstance(): HelperManager
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
        $class = '\\Sprint\\Migration\\Helpers\\' . $name . 'Helper';

        return $this->callHelper($class);
    }

    /**
     * @throws HelperException
     */
    private function callHelper($class)
    {
        if (isset($this->registered[$class])) {
            return $this->registered[$class];
        }

        if (class_exists($class)) {
            $ob = new $class;
            if ($ob instanceof Helper) {
                $this->registered[$class] = $ob;
                return $ob;
            }
        }

        throw new HelperException("Helper \"$class\" not found");
    }

    /**
     * @throws HelperException
     * @deprecated
     * @removed in 4.10
     */
    public function registerHelper(string $name, string $class)
    {
        $this->callHelper($class);
    }
}
