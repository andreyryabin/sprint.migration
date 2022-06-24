<?php

use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Out;
use Sprint\Migration\SchemaManager;
use Sprint\Migration\VersionConfig;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if ($_POST["step_code"] == "schema_export" && check_bitrix_sessid('send_sessid')) {

    $params = !empty($_POST['params']) ? $_POST['params'] : [];
    $checked = !empty($_POST['schema_checked']) ? $_POST['schema_checked'] : [];


    /** @var $versionConfig VersionConfig */
    $schemaManager = new SchemaManager($versionConfig, $params);

    $ok = false;
    $error = true;

    try {
        $schemaManager->export(['name' => $checked]);

        $ok = true;
        $error = false;

    } catch (RestartException $e) {

        $json = json_encode([
            'params' => $schemaManager->getRestartParams(),
        ]);

        ?>
        <script>
            schemaExecuteStep('schema_export', <?=$json?>);
        </script>
        <?php
    } catch (Throwable $e) {
        Out::outException($e);
    }

    $progress = $schemaManager->getProgress();
    foreach ($progress as $type => $val) {
        ?>
        <script>
            schemaProgress('<?=$type?>',<?=$val?>);
        </script>
        <?php
    }

    if ($ok) {
        ?>
        <script>
            schemaProgressReset();
            schemaRefresh();
        </script>
        <?php
    }

    if ($error) {
        ?>
        <script>
            schemaRefresh();
        </script>
        <?php
    }

}
