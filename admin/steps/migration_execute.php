<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_execute" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    $params = !empty($_POST['params']) ? $_POST['params'] : array();
    $version = isset($_POST['version']) ? $_POST['version'] : 0;
    $action = !empty($_POST['action']) ? $_POST['action'] : 0;
    $nextAction = !empty($_POST['next_action']) ? $_POST['next_action'] : 0;

    if (!$version){
        if ($nextAction == 'up'){
            $action = 'up';
            $version = $manager->getOnceVersionFor($action);
        } elseif ($nextAction == 'down') {
            $action = 'down';
            $version = $manager->getOnceVersionFor($action);
        }
    }

    if ($version && $action){
        $success = $manager->executeVersion($version, $action, $params);
        if ($manager->needRestart($version)){

            $json = json_encode(array(
                'params' => $manager->getRestartParams($version),
                'action' => $action,
                'version' => $version,
                'next_action' => $nextAction
            ));

            ?><script>migrationExecuteStep('migration_execute', <?=$json?>);</script><?
        } elseif ($nextAction){
            $json = json_encode(array(
                'next_action' => $nextAction,
            ));

            ?><script>
                migrationMigrationRefresh(function(){
                    migrationExecuteStep('migration_execute', <?=$json?>);
                });
            </script><?
        } else {
            ?><script>migrationMigrationRefresh();</script><?
        }
    } else {
        ?><script>migrationMigrationRefresh();</script><?
    }

    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}