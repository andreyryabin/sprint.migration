<?php

if (!empty($_SERVER["HTTP_HOST"])){
    die('console only');
}

set_time_limit(0);
error_reporting(E_ERROR );

defined('NO_KEEP_STATISTIC') || define('NO_KEEP_STATISTIC', "Y");
defined('NO_AGENT_STATISTIC') || define('NO_AGENT_STATISTIC', "Y");
defined('NOT_CHECK_PERMISSIONS') || define('NOT_CHECK_PERMISSIONS', true);

defined('CACHED_b_iblock_type') || define('CACHED_b_iblock_type', false);
defined('CACHED_b_iblock') || define('CACHED_b_iblock', false);
defined('CACHED_b_iblock_property_enum') || define('CACHED_b_iblock_property_enum', false);

if (empty($_SERVER["DOCUMENT_ROOT"])){
    $_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__ . '/../../../../');
}

$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (\CModule::IncludeModule('sprint.migration')){
    $console = new Sprint\Migration\Console();
    $console->executeConsoleCommand($argv);
} else {
    echo 'need to install module sprint.migration';
}



require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
