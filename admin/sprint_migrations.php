<?php

use Bitrix\Main\Loader;
use Sprint\Migration\ConfigManager;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin.php");

include __DIR__ . '/assets/style.php';

$sperrors = [];

/** @global $APPLICATION CMain */
global $APPLICATION;

try {
    if (!Loader::includeModule('sprint.migration')) {
        throw new Exception('need to install module sprint.migration');
    }

    if ($APPLICATION->GetGroupRight('sprint.migration') == 'D') {
        throw new Exception(Locale::getMessage("ACCESS_DENIED"));
    }

    Module::checkHealth();

    $versionConfig = ConfigManager::getInstance()->get(
        $_REQUEST['config'] ?? VersionEnum::CONFIG_DEFAULT
    );

    $APPLICATION->SetTitle($versionConfig->getTitle());

    if ($versionConfig->getVal('show_admin_interface')) {
        include __DIR__ . '/assets/script.php';
    } else {
        $sperrors[] = Locale::getMessage('ADMIN_INTERFACE_HIDDEN');
    }

} catch (Throwable $exception) {
    $makeRelative = function (string $path, int $depth = 0) {
        $chunks = explode(DIRECTORY_SEPARATOR, $path);
        $chunks = array_slice($chunks, -($depth + 1));
        return '.../' . implode('/', $chunks);
    };

    $sperrors[] = sprintf(
        "[%s] %s (%s) in %s:%d",
        get_class($exception),
        $exception->getMessage(),
        $exception->getCode(),
        $makeRelative($exception->getFile()),
        $exception->getLine()
    );

    foreach ($exception->getTrace() as $err) {
        $sperrors[] = sprintf('%s:%d', $makeRelative($err['file'], 2), $err['line']);
    }
}


include __DIR__ . '/includes/help.php';
include __DIR__ . '/includes/errors.php';

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
