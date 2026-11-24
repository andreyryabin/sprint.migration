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
     * @deprecated
     */
    public function commandList(): void
    {
        $this->output->outWarning('deprecated, use ls');
        $this->commandLs();
    }

    /**
     * @throws MigrationException
     * @throws ConsoleException
     * @publicApi
     * @deprecated
     */
    public function commandDel(): void
    {
        $this->output->outWarning('deprecated, use delete');
        $this->commandDelete();
    }

    /**
     * @publicApi
     * @deprecated
     * @throws MigrationException
     */
    public function commandMi(): void
    {
        $this->output->outWarning('deprecated, use migrate');
        $this->commandMigrate();
    }
}