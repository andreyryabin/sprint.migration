<?php

use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Out;
use Sprint\Migration\SchemaManager;
use Sprint\Migration\VersionConfig;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

$hasSteps = (
    ($_POST["step_code"] == "schema_import") ||
    ($_POST["step_code"] == "schema_test")
);

if ($hasSteps && check_bitrix_sessid('send_sessid')) {
    $params = !empty($_POST['params']) ? $_POST['params'] : [];
    $checked = !empty($_POST['schema_checked']) ? $_POST['schema_checked'] : [];

    /** @var $versionConfig VersionConfig */
    $schemaManager = new SchemaManager($versionConfig, $params);

    if ($_POST["step_code"] == "schema_test") {
        $schemaManager->setTestMode(1);
    } else {
        $schemaManager->setTestMode(0);
    }

    $ok = false;
    $error = false;

    try {
        $schemaManager->import(['name' => $checked]);

        $ok = true;

    } catch (RestartException $e) {

        $json = json_encode([
            'params' => $schemaManager->getRestartParams(),
        ]);

        ?>
        <script>
            schemaExecuteStep('<?=$_POST["step_code"]?>', <?=$json?>);
        </script>
        <?
    } catch (Exception $e) {
        Out::outError($e->getMessage());
        $error = true;

    } catch (Throwable $e) {
        Out::outError($e->getMessage());
        $error = true;
    }

    $progress = $schemaManager->getProgress();
    foreach ($progress as $type => $val) {
        ?>
        <script>
            schemaProgress('<?=$type?>',<?=$val?>);
        </script>
        <?
    }

    if ($ok) {
        ?>
        <script>
            schemaProgressReset();
            schemaRefresh();
        </script>
        <?
    }

    if ($error) {
        ?>
        <script>
            schemaRefresh();
        </script>
        <?
    }
}