<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

$hasSteps = (
    ($_POST["step_code"] == "migration_create") ||
    ($_POST["step_code"] == "migration_reset")
);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $hasSteps && check_bitrix_sessid('send_sessid')) {
    /** @noinspection PhpIncludeInspection */
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    /** @var $versionConfig \Sprint\Migration\VersionConfig */
    $versionManager = new \Sprint\Migration\VersionManager($versionConfig);

    $builderName = !empty($_POST['builder_name']) ? trim($_POST['builder_name']) : '';

    if ($_POST["step_code"] == "migration_create") {
        $builder = $versionManager->createBuilder($builderName, $_POST);
    } else {
        $builder = $versionManager->createBuilder($builderName, array());
    }

    if (!$builder) {
        /** @noinspection PhpIncludeInspection */
        require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
        die();
    }

    if ($_POST["step_code"] == "migration_create") {
        $builder->executeBuilder();

        $builder->renderHtml();

        if ($builder->isRestart()) {
            $json = json_encode($builder->getRestartParams());
            ?>
            <script>migrationBuilder(<?=$json?>);</script><?

        } elseif ($builder->isRebuild()) {
            ?>
            <script>migrationEnableButtons(1);</script><?

        } elseif ($builder->hasActions()) {
            $actions = $builder->getActions();
            foreach ($actions as $action) {
                if ($action['type'] == 'redirect') {
                    ?>
                    <script>window.location.replace("<?=$action['url']?>");</script><?
                }
            }

        } else {
            ?>
            <script>
                migrationMigrationRefresh(function () {
                    migrationScrollList();
                    migrationEnableButtons(1);
                });
            </script><?
        }
    }


    if ($_POST["step_code"] == "migration_reset") {
        $builder->renderHtml();
        ?>
        <script>migrationMigrationRefresh();</script><?
    }

    /** @noinspection PhpIncludeInspection */
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}