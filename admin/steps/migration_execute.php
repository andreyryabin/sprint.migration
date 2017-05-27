<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_execute" && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $params = !empty($_POST['params']) ? $_POST['params'] : array();
    $restart = !empty($_POST['restart']) ? 1 : 0;
    $version = isset($_POST['version']) ? $_POST['version'] : 0;
    $action = !empty($_POST['action']) ? $_POST['action'] : 0;
    $nextAction = !empty($_POST['next_action']) ? $_POST['next_action'] : 0;
    $skipVersions = !empty($_POST['skip_versions']) ? $_POST['skip_versions'] : array();
    $search = !empty($_POST['search']) ? trim($_POST['search']) : '';
    $search = Sprint\Migration\Locale::convertToUtf8IfNeed($search);

    if (!$version) {
        if ($nextAction == 'up' || $nextAction == 'down') {

            $version = 0;
            $action = $nextAction;

            $items = $versionManager->getVersions(array(
                'status' => ($action == 'up') ? 'new' : 'installed',
                'search' => $search,
            ));

            foreach ($items as $aItem) {
                if (!in_array($aItem['version'], $skipVersions)) {
                    $version = $aItem['version'];
                    break;
                }
            }

        }
    }

    if ($version && $action) {

        if (!$restart) {
            Sprint\Migration\Out::out('[%s]%s (%s) start[/]', $action, $version, $action);
        }

        $success = $versionManager->startMigration($version, $action, $params);
        $restart = $versionManager->needRestart($version);

        if ($success && !$restart) {
            Sprint\Migration\Out::out('%s (%s) success', $version, $action);
        }

        if (!$success && !$restart) {
            Sprint\Migration\Out::outError(
                '%s (%s) error: %s',
                $version,
                $action,
                $versionManager->getLastException()->getMessage()
            );

            if ($versionManager->getConfigVal('stop_on_errors')) {
                $nextAction = false;
            } else {
                $skipVersions[] = $version;
            }
        }

        if ($restart) {
            $json = json_encode(array(
                'params' => $versionManager->getRestartParams($version),
                'action' => $action,
                'version' => $version,
                'next_action' => $nextAction,
                'restart' => 1,
                'search' => $search,
            ));

            ?>
            <script>migrationExecuteStep('migration_execute', <?=$json?>);</script><?
        } elseif ($nextAction) {
            $json = json_encode(array(
                'next_action' => $nextAction,
                'skip_versions' => $skipVersions,
                'search' => $search,
            ));

            ?>
            <script>
                migrationMigrationRefresh(function () {
                    migrationExecuteStep('migration_execute', <?=$json?>);
                });
            </script><?
        } else {
            ?>
            <script>
                migrationMigrationRefresh();
            </script><?
        }
    } else {
        ?>
        <script>
            migrationMigrationRefresh();
        </script><?
    }

    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}