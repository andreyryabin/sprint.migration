<?php

use Sprint\Migration\Helpers\SqlHelper;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) {
    die();
}

if (($_POST['step_code'] ?? '') === 'migration_autocomplete' && check_bitrix_sessid()) {
    $source = trim((string)($_POST['source'] ?? ''));
    $search = trim((string)($_POST['search'] ?? ''));
    $items = [];

    if ($source === 'sql_tables') {
        foreach ((new SqlHelper())->searchTables($search) as $tableName) {
            $items[] = [
                'title' => $tableName,
                'value' => $tableName,
            ];
        }
    }

    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
    die();
}
