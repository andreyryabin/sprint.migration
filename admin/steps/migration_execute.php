<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_execute" && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $params = !empty($_POST['params']) ? $_POST['params'] : array();
    $version = isset($_POST['version']) ? $_POST['version'] : 0;
    $action = !empty($_POST['action']) ? $_POST['action'] : 0;
    $nextAction = !empty($_POST['next_action']) ? $_POST['next_action'] : 0;
    $skipVersions = !empty($_POST['skip_versions']) ? $_POST['skip_versions'] : array();

    if (!$version){
        if ($nextAction == 'up' || $nextAction == 'down'){

            $version = 0;
            $action = $nextAction;

            $items = $versionManager->getVersions($action);

            foreach ($items as $aItem){
                if (!in_array($aItem['version'], $skipVersions)){
                    $version = $aItem['version'];
                    break;
                }
            }

        }
    }

    if ($version && $action){
        $success = $versionManager->startMigration($version, $action, $params);

        if ($versionManager->needRestart($version)){
            $json = json_encode(array(
                'params' => $versionManager->getRestartParams($version),
                'action' => $action,
                'version' => $version,
                'next_action' => $nextAction
            ));

            ?><script>migrationExecuteStep('migration_execute', <?=$json?>);</script><?
        } elseif ($nextAction){

            if (!$success) {
                $skipVersions[] = $version;
            }

            $json = json_encode(array(
                'next_action' => $nextAction,
                'skip_versions' => $skipVersions
            ));

            ?><script>
                migrationMigrationRefresh(function(){
                    migrationExecuteStep('migration_execute', <?=$json?>);
                });
            </script><?
        } else {
            ?><script>
                migrationMigrationRefresh();
            </script><?
        }
    } else {
        ?><script>
            migrationMigrationRefresh();
        </script><?
    }

    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}