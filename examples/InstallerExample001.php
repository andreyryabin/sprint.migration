<?php

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Installer;

if (CModule::IncludeModule('sprint.migration')) {
    try {
        $installer = new Installer([
            'migration_dir'          => __DIR__ . '/my_module_migrations/',
            'migration_dir_absolute' => true, // if migration_dir is absolute
        ]);

        //install all
        $installer->up();

        //rollback all
        $installer->down();

        //rollback versions by filter
        $installer->executeAll([
            'description' => 'Task123'
        ], 'down');

        //install current version
        $installer->executeVersion('Version1234', 'up');

    } catch (MigrationException $e) {
        echo $e->getMessage();
    }
}

