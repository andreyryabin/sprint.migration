<?php

use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Locale;
use Sprint\Migration\Out;
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
    $versionName = !empty($_POST['version']) ? $_POST['version'] : '';
    $action = !empty($_POST['action']) ? $_POST['action'] : '';
    $nextAction = !empty($_POST['next_action']) ? $_POST['next_action'] : '';
    $settag = !empty($_POST['settag']) ? trim($_POST['settag']) : '';
    $search = !empty($_POST['search']) ? trim($_POST['search']) : '';
    $search = Sprint\Migration\Locale::convertToUtf8IfNeed($search);

    $migrationView = !empty($_POST['migration_view']) ? trim($_POST['migration_view']) : '';

    $filter = [
        'search'   => $search,
        'tag'      => '',
        'modified' => '',
        'older'    => '',
        'actual'   => '',
    ];

    if ($migrationView == 'migration_view_tag') {
        $filter['tag'] = $search;
        $filter['search'] = '';
    } elseif ($migrationView == 'migration_view_modified') {
        $filter['modified'] = 1;
    } elseif ($migrationView == 'migration_view_older') {
        $filter['older'] = 1;
    }

    if (!$versionName) {
        if ($nextAction == VersionEnum::ACTION_UP || $nextAction == VersionEnum::ACTION_DOWN) {
            $action = $nextAction;
            $versionName = $versionManager->getOnceForExecute($filter, $action);
        }
    }

    if ($versionName && $action) {
        if (!$restart) {
            Out::out('[%s]%s (%s) start[/]', $action, $versionName, $action);
        }

        $success = $versionManager->startMigration(
            $versionName,
            $action,
            $params,
            $settag
        );

        $restart = ($success) ? $versionManager->needRestart() : $restart;

        if ($success && !$restart) {
            Out::out('%s (%s) success', $versionName, $action);

            if ($nextAction) {
                $json = json_encode([
                    'next_action'    => $nextAction,
                    'settag'         => $settag,
                    'search'         => $search,
                    'migration_view' => $migrationView,
                ]);

                ?>
                <script>
                    migrationListRefresh(function () {
                        migrationExecuteStep('migration_execute', <?=$json?>);
                    });
                </script><?php
            } else {
                ?>
                <script>
                    migrationListRefresh();
                </script><?php
            }
        }

        if ($success && $restart) {
            $json = json_encode([
                'params'         => $versionManager->getRestartParams(),
                'action'         => $action,
                'version'        => $versionName,
                'next_action'    => $nextAction,
                'restart'        => 1,
                'search'         => $search,
                'migration_view' => $migrationView,
                'settag'         => $settag,
            ]);

            ?>
            <script>migrationExecuteStep('migration_execute', <?=$json?>);</script><?php
        }

        if (!$success) {
            Out::outException($versionManager->getLastException());

            $json = json_encode([
                'params'         => $params,
                'action'         => $action,
                'version'        => $versionName,
                'next_action'    => $nextAction,
                'restart'        => 1,
                'search'         => $search,
                'migration_view' => $migrationView,
                'settag'         => $settag,
            ]);
            ?>
            <script>
                (function () {
                    let $btn = $('<input type="button" value="<?= Locale::getMessage('RESTART_AGAIN') ?>">');
                    $btn.bind('click', function () {
                        migrationExecuteStep('migration_execute', <?=$json?>);
                    })
                    $('#migration_actions').empty().append($btn);
                })();

                migrationEnableButtons(1);
            </script><?php
        }
    } else {
        ?>
        <script>
            migrationListRefresh();
        </script><?php
    }
}
