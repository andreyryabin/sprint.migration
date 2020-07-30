<?php

use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Out;
use Sprint\Migration\SchemaManager;
use Sprint\Migration\VersionConfig;
use Bitrix\Main\Application;


if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}
$request = Application::getInstance()->getContext()->getRequest();


if ($request->getPost('step_code') == "schema_export" && check_bitrix_sessid('send_sessid')) {

    $params = !empty($request->getPost('params')) ? $request->getPost('params') : [];
    $checked = !empty($request->getPost('schema_checked')) ? $request->getPost('schema_checked') : [];


    /** @var $versionConfig VersionConfig */
    $schemaManager = new SchemaManager($versionConfig, $params);

    $ok = false;

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