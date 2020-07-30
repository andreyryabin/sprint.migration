<?php

use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Out;
use Sprint\Migration\SchemaManager;
use Sprint\Migration\VersionConfig;
use Bitrix\Main\Application;


if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}
$request = Application::getInstance()->getContext()->getRequest();
$hasSteps = (
    ($request->getPost('step_code') == 'schema_import') ||
    ($request->getPost('step_code') == 'schema_test')
);

if ($hasSteps && check_bitrix_sessid('send_sessid')) {
    $params = !empty($request->getPost('params')) ? $request->getPost('params') : [];
    $checked = !empty($request->getPost('schema_checked')) ? $request->getPost('schema_checked') : [];
    $stepCode = !empty($request->getPost('step_code')) ? $request->getPost('step_code') : '';

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
            schemaProgress('<?=$type?>', <?=$val?>);
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
