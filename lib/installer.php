<?php

namespace Sprint\Migration;

use Exception;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;

class Installer
{
    private $versionManager;

    /**
     * Installer constructor.
     *
     * @param array $configValues
     *
     * @throws Exception
     */
    public function __construct($configValues = [])
    {
        $this->versionManager = new VersionManager(
            new VersionConfig('installer', $configValues)
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
    protected function executeAll($filter, $action)
    {
        $versionNames = $this->versionManager->getListForExecute($filter, $action);

        foreach ($versionNames as $versionName) {
            $this->executeVersion($versionName, $action);
        }
    }

    /**
     * @param string $version
     * @param string $action
     *
     * @throws MigrationException
     * @return bool
     */
    protected function executeVersion($version, $action = VersionEnum::ACTION_UP)
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
