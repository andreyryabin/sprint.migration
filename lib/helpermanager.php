<?php

namespace Sprint\Migration;

use Sprint\Migration\Helpers\AgentHelper;
use Sprint\Migration\Helpers\BlogHelper;
use Sprint\Migration\Helpers\CultureHelper;
use Sprint\Migration\helpers\DeliveryServiceHelper;
use Sprint\Migration\Helpers\EventHelper;
use Sprint\Migration\Helpers\FormHelper;
use Sprint\Migration\Helpers\ForumHelper;
use Sprint\Migration\Helpers\HlblockHelper;
use Sprint\Migration\Helpers\IblockHelper;
use Sprint\Migration\Helpers\LangHelper;
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
 * @method OrderPropertiesHelper    OrderProperties()
 * @method CultureHelper            Culture()
 */
class HelperManager extends HelperRegistry
{
}
