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
        '.github/FUNDING.yml',
        'admin/assets/schema.js',
        'admin/assets/schema.php',
        'admin/includes/schema.php',
        'admin/includes/search.php',
        'admin/pages/support.php',
        'admin/steps/schema_export.php',
        'admin/steps/schema_import.php',
        'admin/steps/schema_list.php',
        'lib/abstractexchange.php',
        'lib/abstractschema.php',
        'lib/consolegrid.php',
        'lib/exceptions/exchangeexception.php',
        'lib/exceptions/schemaexception.php',
        'lib/exchange/hlblockelementsexport.php',
        'lib/exchange/hlblockelementsimport.php',
        'lib/exchange/iblockelementsexport.php',
        'lib/exchange/iblockelementsimport.php',
        'lib/exchange/medialibelementsexport.php',
        'lib/exchange/medialibelementsimport.php',
        'lib/exchangeentity.php',
        'lib/exchangemanager.php',
        'lib/helpers/adminiblockhelper.php',
        'lib/outtrait.php',
        'lib/schema/agentschema.php',
        'lib/schema/eventschema.php',
        'lib/schema/groupschema.php',
        'lib/schema/hlblockschema.php',
        'lib/schema/iblockschema.php',
        'lib/schema/optionschema.php',
        'lib/schema/usertypeentitiesschema.php',
        'lib/schemamanager.php',
        'lib/traits/exceptiontrait.php',
        'lib/traits/exitmessagetrait.php',
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
