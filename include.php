<?php

use Sprint\Migration\Enum\EventsEnum;
use Sprint\Migration\Module;

include(__DIR__ . '/locale/ru.php');
include(__DIR__ . '/locale/en.php');

class_alias('\Sprint\Migration\Builder', '\Sprint\Migration\AbstractBuilder');


AddEventHandler(
    Module::ID,
    EventsEnum::ON_SEARCH_CONFIG_FILES,
    fn() => Module::getPhpInterfaceDir()
);
