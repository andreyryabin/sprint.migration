<?php
$module_id = "sprint.migration";

global $APPLICATION;

$MODULE_RIGHT = $APPLICATION->GetGroupRight($module_id);

if (!($MODULE_RIGHT >= "R"))
    $APPLICATION->AuthForm("ACCESS_DENIED");

