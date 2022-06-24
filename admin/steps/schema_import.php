<?php

use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Out;
use Sprint\Migration\SchemaManager;
use Sprint\Migration\VersionConfig;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$hasSteps = (
    ($_POST['step_code'] == 'schema_import') ||
    ($_POST['step_code'] == 'schema_test')
);

if ($hasSteps && check_bitrix_sessid('send_sessid')) {
    $params = !empty($_POST['params']) ? $_POST['params'] : [];
    $checked = !empty($_POST['schema_checked']) ? $_POST['schema_checked'] : [];
    $stepCode = !empty($_POST['step_code']) ? $_POST['step_code'] : '';

    /** @var $versionConfig VersionConfig */
    $schemaManager = new SchemaManager($versionConfig, $params);

    if ($stepCode == 'schema_test') {
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
            schemaExecuteStep('<?=$stepCode?>', <?=$json?>);
        </script>
        <?php
    } catch (Throwable $e) {
        Out::outException($e);
        $error = true;
    }

    $progress = $schemaManager->getProgress();
    foreach ($progress as $type => $val) {
        ?>
        <script>
            schemaProgress('<?=$type?>', <?=$val?>);
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
