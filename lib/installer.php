<?php

namespace Sprint\Migration;

use Exception;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;

class Installer
{
    private VersionManager $versionManager;

    /**
     * Installer constructor.
     *
     * @param array $configValues
     *
     * @throws Exception
     */
    public function __construct(array $configValues = [])
    {
        $this->versionManager = new VersionManager(
            new VersionConfig('installer', $configValues)
        );
    }

    /**
     * @throws MigrationException
     */
    public function up(): void
    {
        $this->executeAll([], VersionEnum::ACTION_UP);
    }

    /**
     * @throws MigrationException
     */
    public function down(): void
    {
        $this->executeAll([], VersionEnum::ACTION_DOWN);
    }

    /**
     * @throws MigrationException
     */
    public function executeAll(array $filter, string $action, string $tag = ''): void
    {
        foreach ($this->versionManager->getListForExecute($filter, $action) as $version) {
            $this->executeVersion($version, $action, $tag);
        }
    }

    /**
     * @throws MigrationException
     */
    public function executeVersion(string $version, string $action, string $tag = ''): bool
    {
        $params = [];
        do {
            $exec = 0;

            $success = $this->versionManager->startMigration(
                $version,
                $action,
                $params,
                $tag
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
