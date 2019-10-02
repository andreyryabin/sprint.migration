<?php

/** @var CUpdater $updater */
if ($updater && $updater instanceof CUpdater) {

    if (!function_exists('sprint_migration_rmdir')) {
        function sprint_migration_rmdir($dir)
        {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? sprint_migration_rmdir("$dir/$file") : unlink("$dir/$file");
            }
            return rmdir($dir);
        }
    }

    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
//        sprint_migration_rmdir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sprint.migration/lib/helpers/useroptions/');
//        sprint_migration_rmdir($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sprint.migration/admin/');
//        unlink($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sprint.migration/loader.php');
    }

    if (is_dir(__DIR__ . '/install/gadgets/')) {
        $updater->CopyFiles("install/gadgets/", "gadgets/");
    }

}