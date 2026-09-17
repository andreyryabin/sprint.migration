<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helpers\AgentHelper;
use Sprint\Migration\Helpers\BlogExchangeHelper;
use Sprint\Migration\Helpers\BlogHelper;
use Sprint\Migration\Helpers\CultureHelper;
use Sprint\Migration\helpers\DeliveryServiceHelper;
use Sprint\Migration\Helpers\EventHelper;
use Sprint\Migration\Helpers\FormHelper;
use Sprint\Migration\Helpers\ForumHelper;
use Sprint\Migration\Helpers\HlblockExchangeHelper;
use Sprint\Migration\Helpers\HlblockHelper;
use Sprint\Migration\Helpers\IblockExchangeHelper;
use Sprint\Migration\Helpers\IblockHelper;
use Sprint\Migration\Helpers\LangHelper;
use Sprint\Migration\Helpers\MedialibExchangeHelper;
use Sprint\Migration\Helpers\MedialibHelper;
use Sprint\Migration\Helpers\OptionHelper;
use Sprint\Migration\Helpers\OrderPropertiesHelper;
use Sprint\Migration\Helpers\SaleDiscountHelper;
use Sprint\Migration\Helpers\SiteHelper;
use Sprint\Migration\Helpers\SqlHelper;
use Sprint\Migration\Helpers\SubscribeHelper;
use Sprint\Migration\Helpers\TaskHelper;
use Sprint\Migration\Helpers\TextHelper;
use Sprint\Migration\Helpers\UserGroupHelper;
use Sprint\Migration\Helpers\UserHelper;
use Sprint\Migration\Helpers\UserOptionsHelper;
use Sprint\Migration\Helpers\UserTypeEntityHelper;
use Sprint\Migration\Helpers\VoteHelper;

/**
 * @method IblockHelper             Iblock()
 * @method HlblockHelper            Hlblock()
 * @method AgentHelper              Agent()
 * @method BlogHelper               Blog()
 * @method BlogExchangeHelper       BlogExchange()
 * @method EventHelper              Event()
 * @method LangHelper               Lang()
 * @method SiteHelper               Site()
 * @method UserOptionsHelper        UserOptions()
 * @method UserTypeEntityHelper     UserTypeEntity()
 * @method UserGroupHelper          UserGroup()
 * @method UserHelper               User()
 * @method TaskHelper               Task()
 * @method OptionHelper             Option()
 * @method FormHelper               Form()
 * @method ForumHelper              Forum()
 * @method VoteHelper               Vote()
 * @method DeliveryServiceHelper    DeliveryService()
 * @method SaleDiscountHelper       SaleDiscount()
 * @method SqlHelper                Sql()
 * @method SubscribeHelper          Subscribe()
 * @method MedialibHelper           Medialib()
 * @method TextHelper               Text()
 * @method IblockExchangeHelper     IblockExchange()
 * @method HlblockExchangeHelper    HlblockExchange()
 * @method MedialibExchangeHelper   MedialibExchange()
 * @method OrderPropertiesHelper   OrderProperties()
 * @method CultureHelper   Culture()
 */
class HelperManager
{
    private static ?HelperManager $instance = null;
    private array $registered = [];
    private array $cache = [];

    public static function getInstance(): HelperManager
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @param $name
     * @param $arguments
     *
     * @return Helper
     * @throws HelperException
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
     * @throws HelperException
     */
    protected function callHelper(string $name): Helper
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        $default = '\\Sprint\\Migration\\Helpers\\' . $name . 'Helper';

        $class = $this->registered[$name] ?? $default;

        if (!class_exists($class)) {
            throw new HelperException("Helper \"$name\" in \"$class\" not found");
        }
        $ob = new $class;
        if (!($ob instanceof Helper)) {
            throw new HelperException("Class \"$class\" is not helper");
        }

        if (!$ob->isEnabled()) {
            throw new HelperException("Helper \"$name\" disabled");
        }

        $this->cache[$name] = $ob;
        return $ob;

    }
}
