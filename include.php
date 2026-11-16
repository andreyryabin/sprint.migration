<?php

use Bitrix\Main\Localization\Loc;
use Sprint\Migration\Enum\EventsEnum;
use Sprint\Migration\Module;

Loc::loadMessages(__FILE__);

class_alias('\Sprint\Migration\Builder', '\Sprint\Migration\AbstractBuilder');

AddEventHandler(
    Module::ID,
    EventsEnum::ON_SEARCH_CONFIG_FILES,
    fn() => Module::getPhpInterfaceDir()
);
