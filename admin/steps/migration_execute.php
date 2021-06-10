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
    $settag = !empty($_POST['settag']) ? trim($_POST['settag']) : '';


    $search = !empty($_POST['search']) ? trim($_POST['search']) : '';
    $search = Sprint\Migration\Locale::convertToUtf8IfNeed($search);

    $filter = !empty($_POST['filter']) ? trim($_POST['filter']) : '';

    $filterVersion = [
        'search' => $search,
        'tag' => '',
        'modified' => '',
        'older' => '',
    ];

    if ($filter == 'migration_view_tag') {
        $filterVersion['tag'] = $search;
        $filterVersion['search'] = '';
    } elseif ($filter == 'migration_view_modified') {
        $filterVersion['modified'] = 1;
    } elseif ($filter == 'migration_view_older') {
        $filterVersion['older'] = 1;
    }


    if (!$version) {
        if ($nextAction == VersionEnum::ACTION_UP || $nextAction == VersionEnum::ACTION_DOWN) {

            $version = 0;
            $action = $nextAction;

            $filterVersion = array_merge([
                'status' => ($action == VersionEnum::ACTION_UP) ? VersionEnum::STATUS_NEW : VersionEnum::STATUS_INSTALLED,
            ], $filterVersion);

            $items = $versionManager->getVersions($filterVersion);

            foreach ($items as $item) {
                if (!in_array($item['version'], $skipVersions)) {
                    $version = $item['version'];
                    break;
                }
            }

        }
    }

    if ($version && $action) {

        if (!$restart) {
            Sprint\Migration\Out::out('[%s]%s (%s) start[/]', $action, $version, $action);
        }

        $success = $versionManager->startMigration($version, $action, $params, false, $settag);
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
                'filter' => $filter,
                'settag' => $settag,
            ]);

            ?>
            <script>migrationExecuteStep('migration_execute', <?=$json?>);</script><?php
        } elseif ($nextAction) {
            $json = json_encode([
                'next_action' => $nextAction,
                'skip_versions' => $skipVersions,
                'settag' => $settag,
                'search' => $search,
                'filter' => $filter,
            ]);

            ?>
            <script>
                migrationMigrationRefresh(function () {
                    migrationExecuteStep('migration_execute', <?=$json?>);
                });
            </script><?php
        } else {
            ?>
            <script>
                migrationMigrationRefresh();
            </script><?php
        }
    } else {
        ?>
        <script>
            migrationMigrationRefresh();
        </script><?php
    }

}
