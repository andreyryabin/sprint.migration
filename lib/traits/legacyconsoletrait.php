<?php

namespace Sprint\Migration\Traits;

use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\ConsoleException;
use Sprint\Migration\Exceptions\MigrationException;

trait LegacyConsoleTrait
{

    /**
     * @throws BuilderException
     * @throws ConsoleException
     * @deprecated
     * @publicApi
     */
    public function commandRun(): void
    {
        $this->output->outWarning('deprecated, use make');
        $this->executeBuilderByArgs();
    }

    /**
     * @throws BuilderException
     * @throws ConsoleException
     * @deprecated
     * @publicApi
     */
    public function commandAdd(): void
    {
        $this->output->outWarning('deprecated, use make');
        $this->executeBuilderByArgs();
    }


    /**
     * @publicApi
     * @throws MigrationException
     * @throws ConsoleException
     */
    public function commandLs(): void
    {
        $this->commandList();
    }

    /**
     * @publicApi
     */
    public function commandBs(): void
    {
        $this->commandBuilders();
    }

    /**
     * @throws MigrationException
     * @throws ConsoleException
     * @publicApi
     */
    public function commandDel(): void
    {
        $this->commandDelete();
    }

    /**
     * @publicApi
     * @throws MigrationException
     */
    public function commandMi(): void
    {
        $this->output->outWarning('deprecated, use migrate');
        $this->commandMigrate();
    }
}