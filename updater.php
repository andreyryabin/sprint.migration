<?php

/** @var CUpdater $updater */
if ($updater && $updater instanceof CUpdater) {
    $moduledir = realpath($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sprint.migration');
    if (!$moduledir) {
        return;
    }


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

    $pathsToRemove = [
        '/admin/steps',
        '/admin/fields',
        '/locale',
    ];

    $filesToRemove = [
        '/admin/includes/builder_form.php',
        '/admin/includes/builder_group.php',
        '/admin/includes/interface.php',
        '/admin/includes/version.php',

        '/admin/assets/script.js',
        '/admin/assets/style.css',
        '/admin/assets/version.php',

        '/lib/console.php',
    ];

    foreach ($pathsToRemove as $path) {
        sprint_migration_rmdir($moduledir  . $path);
    }
    foreach ($filesToRemove as $file) {
        if (is_file($moduledir .  $file)) {
            unlink($moduledir .  $file);
        }
    }
    if (is_dir(__DIR__ . '/install/gadgets/')) {
        $updater->CopyFiles("install/gadgets/", "gadgets/");
    }
    //v 6.0.0
}
