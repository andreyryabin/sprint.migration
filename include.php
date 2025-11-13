<?php

use Sprint\Migration\Enum\EventsEnum;
use Sprint\Migration\Module;

require_once __DIR__ . '/lib/locale.php';
Sprint\Migration\Locale::loadDefault();

class_alias('\Sprint\Migration\Builder', '\Sprint\Migration\AbstractBuilder');

AddEventHandler(
    Module::ID,
    EventsEnum::ON_SEARCH_CONFIG_FILES,
    fn() => Module::getPhpInterfaceDir()
);
