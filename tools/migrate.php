<?php

use Bitrix\Main\Loader;

if (php_sapi_name() != 'cli') {
    die('Can not run in this mode. Bye!');
}

set_time_limit(0);
error_reporting(E_ERROR);

defined('NO_AGENT_CHECK') || define('NO_AGENT_CHECK', true);
defined('NO_KEEP_STATISTIC') || define('NO_KEEP_STATISTIC', "Y");
defined('NO_AGENT_STATISTIC') || define('NO_AGENT_STATISTIC', "Y");
defined('NOT_CHECK_PERMISSIONS') || define('NOT_CHECK_PERMISSIONS', true);

if (empty($_SERVER["DOCUMENT_ROOT"])) {
    $_SERVER["DOCUMENT_ROOT"] = realpath(__DIR__ . '/../../../../');
}

$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

try {

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

    if (!Loader::includeModule('sprint.migration')) {
        /** @var sprint_migration $ob */
        if ($ob = CModule::CreateModuleObject('sprint.migration')) {
            $ob->DoInstall();
        }
    }

    if (!Loader::includeModule('sprint.migration')) {
        throw new Exception('need to install module sprint.migration');
    }

    Sprint\Migration\Module::checkHealth();

    $console = new Sprint\Migration\Console($argv);
    $console->executeConsoleCommand();

    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_after.php");

} catch (Throwable $exception) {

    $makeRelative = function (string $path, int $depth = 0) {
        $chunks = explode(DIRECTORY_SEPARATOR, $path);
        $chunks = array_slice($chunks, -($depth + 1));

        return '.../' . implode('/', $chunks);
    };

    fwrite(STDOUT, sprintf(
        "[%s] %s (%s) in %s:%d\n",
        get_class($exception),
        $exception->getMessage(),
        $exception->getCode(),
        $exception->getFile(),
        $exception->getLine()
    ));

    foreach ($exception->getTrace() as $err) {
        fwrite(STDOUT, sprintf("%s:%d\n", $makeRelative($err['file'], 2), $err['line']));
    }

    die(1);
}




