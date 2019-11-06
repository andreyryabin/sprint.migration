<?php

use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\VersionConfig;
use Sprint\Migration\VersionManager;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}


if ($_POST["step_code"] == "migration_execute" && check_bitrix_sessid('send_sessid')) {

    /** @var $versionConfig VersionConfig */
    $versionManager = new VersionManager($versionConfig);

    $params = !empty($_POST['params']) ? $_POST['params'] : [];
    $restart = !empty($_POST['restart']) ? 1 : 0;
    $version = isset($_POST['version']) ? $_POST['version'] : 0;
    $action = !empty($_POST['action']) ? $_POST['action'] : 0;
    $nextAction = !empty($_POST['next_action']) ? $_POST['next_action'] : 0;
    $skipVersions = !empty($_POST['skip_versions']) ? $_POST['skip_versions'] : [];
    $search = !empty($_POST['search']) ? trim($_POST['search']) : '';
    $search = Sprint\Migration\Locale::convertToUtf8IfNeed($search);
    $addtag = !empty($_POST['addtag']) ? trim($_POST['addtag']) : '';

    if (!$version) {
        if ($nextAction == VersionEnum::ACTION_UP || $nextAction == VersionEnum::ACTION_DOWN) {

            $version = 0;
            $action = $nextAction;

            $items = $versionManager->getVersions([
                'status' => ($action == VersionEnum::ACTION_UP) ? VersionEnum::STATUS_NEW : VersionEnum::STATUS_INSTALLED,
                'search' => $search,
            ]);

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

        $success = $versionManager->startMigration($version, $action, $params, false, $addtag);
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

            if ($versionConfig->getVal('stop_on_errors')) {
                $nextAction = false;
            } else {
                $skipVersions[] = $version;
            }
        }

        if ($restart) {
            $json = json_encode([
                'params' => $versionManager->getRestartParams($version),
                'action' => $action,
                'version' => $version,
                'next_action' => $nextAction,
                'restart' => 1,
                'search' => $search,
            ]);

            ?>
            <script>migrationExecuteStep('migration_execute', <?=$json?>);</script><?
        } elseif ($nextAction) {
            $json = json_encode([
                'next_action' => $nextAction,
                'skip_versions' => $skipVersions,
                'search' => $search,
                'addtag' => $addtag,
            ]);

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

}