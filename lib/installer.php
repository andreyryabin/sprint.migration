<?php

namespace Sprint\Migration;

use Exception;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;

class Installer
{
    private VersionManager $versionManager;

    /**
     * @throws Exception
     */
    public function __construct(array $configValues = [])
    {
        $this->versionManager = new VersionManager(
            new VersionConfig(VersionEnum::CONFIG_INSTALLER, $configValues)
        );
    }

    /**
     * @throws MigrationException
     */
    public function up()
    {
        $this->executeAll([], VersionEnum::ACTION_UP);
    }

    /**
     * @throws MigrationException
     */
    public function down()
    {
        $this->executeAll([], VersionEnum::ACTION_DOWN);
    }

    /**
     * @throws MigrationException
     */
    protected function executeAll(array $filter, string $action)
    {
        $versionNames = $this->versionManager->getListForExecute($filter, $action);

        foreach ($versionNames as $versionName) {
            $this->executeVersion($versionName, $action);
        }
    }

    /**
     * @throws MigrationException
     */
    protected function executeVersion(string $version, string $action = VersionEnum::ACTION_UP): bool
    {
        $params = [];
        do {
            $exec = 0;

            $success = $this->versionManager->startMigration(
                $version,
                $action,
                $params
            );

            $restart = $this->versionManager->needRestart();

            if ($restart) {
                $params = $this->versionManager->getRestartParams();
                $exec = 1;
            }

            if (!$success && !$restart) {
                if ($this->versionManager->getLastException()) {
                    throw $this->versionManager->getLastException();
                }
            }
        } while ($exec == 1);

        return $success;
    }
}
