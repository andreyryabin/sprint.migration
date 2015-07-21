<?php

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["step_code"] == "migration_summary" && check_bitrix_sessid('send_sessid')) {
    require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_admin_js.php");

    if (\COption::GetOptionString('sprint.migration', 'admin_versions_view') != 'summary'){
        \COption::SetOptionString('sprint.migration', 'admin_versions_view', 'summary');
    }

    $summary = $manager->getSummaryVersions();
    $titles = array(
        'is_new' => GetMessage('SPRINT_MIGRATION_IS_NEW'),
        'is_success' => GetMessage('SPRINT_MIGRATION_IS_SUCCESS'),
        'is_unknown' => GetMessage('SPRINT_MIGRATION_IS_UNKNOWN'),
    );
    ?>
    <?foreach ($summary as $type => $cnt): ?>
        <div class="c-migration-block">
            <span class="c-migration-item-<?= $type ?>"><?=$titles[$type]?></span>: <span><?= $cnt ?></span>
        </div>
    <?endforeach ?>
    <?
    require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/epilog_admin_js.php");
    die();
}