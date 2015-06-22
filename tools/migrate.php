<?php

if (!empty($_SERVER["HTTP_HOST"])){
    die('console only');
}

set_time_limit(0);
error_reporting(E_ERROR );

define("NO_KEEP_STATISTIC", "Y");
define("NO_AGENT_STATISTIC","Y");
define("NOT_CHECK_PERMISSIONS", true);

$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

if (\CModule::IncludeModule('sprint.migration')){
    $manager = new Sprint\Migration\Manager();
    $manager->executeConsoleCommand($argv);

} else {
    echo 'need to install module sprint.migration';
}



require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
