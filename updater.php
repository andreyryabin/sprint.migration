<?php

/** @var CUpdater $updater */
if ($updater && $updater instanceof \CUpdater) {

    if (!function_exists('sprint_migration_rmdir')) {
        function sprint_migration_rmdir($dir) {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? sprint_migration_rmdir("$dir/$file") : unlink("$dir/$file");
            }
            return rmdir($dir);
        }
    }

    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
//        sprint_migration_rmdir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sprint.migration/classes/');
//        sprint_migration_rmdir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sprint.migration/templates/');
//        sprint_migration_rmdir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sprint.migration/admin/');
//        unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sprint.migration/loader.php');
    }

}