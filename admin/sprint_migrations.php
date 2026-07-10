<?php

use Bitrix\Main\Loader;
use Sprint\Migration\ConfigManager;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\Output;
use Sprint\Migration\Output\HtmlOutput;
use function Sprint\Migration\Output\null;

require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_before.php");

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

    Output::getInstance()
        ->addOutput(new HtmlOutput())
        ->addLogger($versionConfig->getLogger());

    if ($_SERVER["REQUEST_METHOD"] == "POST" && $versionConfig->getVal('show_admin_interface')) {
        require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

        try {
            include __DIR__ . '/steps/migration_execute.php';
            include __DIR__ . '/steps/migration_list.php';
            include __DIR__ . '/steps/migration_status.php';
            include __DIR__ . '/steps/migration_create.php';
            include __DIR__ . '/steps/migration_mark.php';
            include __DIR__ . '/steps/migration_delete.php';
            include __DIR__ . '/steps/migration_settag.php';
            include __DIR__ . '/steps/migration_transfer.php';
            include __DIR__ . '/steps/migration_autocomplete.php';
        } catch (Throwable $e) {
            (new HtmlOutput())->outException($e);
        }

        require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
        die();
    }

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");

    $APPLICATION->SetTitle($versionConfig->getTitle());

    CJSCore::Init(["jquery3"]);

    if ($versionConfig->getVal('show_admin_interface')) {
        include __DIR__ . '/includes/version.php';
        include __DIR__ . '/assets/version.php';
    }

    $sperrors = [];
    if (!$versionConfig->getVal('show_admin_interface')) {
        $sperrors[] = Locale::getMessage('ADMIN_INTERFACE_HIDDEN');
    }

    include __DIR__ . '/includes/errors.php';
    include __DIR__ . '/includes/help.php';
    include __DIR__ . '/assets/style.php';

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
} catch (Throwable $exception) {
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_after.php");


    $makeRelative = function (string $path, int $depth = 0) {
        $chunks = explode(DIRECTORY_SEPARATOR, $path);
        $chunks = array_slice($chunks, -($depth + 1));

        return '.../' . implode('/', $chunks);
    };

    $sperrors = [];

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

    include __DIR__ . '/includes/errors.php';
    include __DIR__ . '/includes/help.php';
    include __DIR__ . '/assets/style.php';

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin.php");
}
