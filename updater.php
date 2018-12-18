<?php

if (!function_exists('sprint_migration_rmdir')) {
    function sprint_migration_rmdir($dir) {
        if ($objs = glob($dir . "/*")) {
            foreach ($objs as $obj) {
                is_dir($obj) ? sprint_migration_rmdir($obj) : unlink($obj);
            }
        }
        rmdir($dir);
    }

    //sprint_migration_rmdir(__DIR__ . '/classes/');
}


