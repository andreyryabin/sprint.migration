<?php

global $APPLICATION;

try {

    if (!\CModule::IncludeModule('sprint.migration')) {
        Throw new \Exception('need to install module sprint.migration');
    }

    if (!$APPLICATION->GetGroupRight("sprint.migration") >= "R") {
        Throw new \Exception(GetMessage("ACCESS_DENIED"));
    }

    \Sprint\Migration\Module::checkHealth();


    include __DIR__ . '/admin/includes/options.php';
    include __DIR__ . '/admin/assets/style.php';

} catch (\Exception $e) {


    $sperrors = array();
    $sperrors[] = $e->getMessage();

    include __DIR__ . '/admin/includes/errors.php';
    include __DIR__ . '/admin/includes/help.php';
    include __DIR__ . '/admin/assets/style.php';

}