<?php

/** @var CUpdater $updater */
if ($updater && $updater instanceof CUpdater) {
    if (!function_exists('sprint_migration_rmdir')) {
        function sprint_migration_rmdir($dir)
        {
            if (!is_dir($dir)) {
                return false;
            }

            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                (is_dir("$dir/$file")) ? sprint_migration_rmdir("$dir/$file") : unlink("$dir/$file");
            }
            return rmdir($dir);
        }
    }


    $filesToRemove = [
//        '.github/FUNDING.yml',
//        'admin/assets/....js',
//        'lib/traits/....php',
    ];

    $moduleRootDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sprint.migration/';

    foreach ($filesToRemove as $file) {
        if (is_file($moduleRootDir . $file)) {
            unlink($moduleRootDir . $file);
        }
    }

    if (is_dir(__DIR__ . '/install/gadgets/')) {
        $updater->CopyFiles("install/gadgets/", "gadgets/");
    }

    //v 4.12.4
}
