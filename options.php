<?php

global $APPLICATION;

use Bitrix\Main\Loader;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;

try {
    if (!Loader::includeModule('sprint.migration')) {
        throw new Exception('need to install module sprint.migration');
    }

    if ($APPLICATION->GetGroupRight('sprint.migration') == 'D') {
        throw new Exception(Locale::getMessage("ACCESS_DENIED"));
    }

    Module::checkHealth();

    include __DIR__ . '/admin/includes/options.php';
    include __DIR__ . '/admin/includes/help.php';
    include __DIR__ . '/admin/assets/style.php';
} catch (Throwable $exception) {
    include __DIR__ . '/admin/includes/errors.php';
    include __DIR__ . '/admin/assets/style.php';
}
