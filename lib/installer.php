<?php

namespace Sprint\Migration;

use Exception;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Output\OutputFactory;
use Throwable;

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
     * @throws MigrationException|Throwable
     */
    public function up(): void
    {
        $this->executeAll([], VersionEnum::ACTION_UP);
    }

    /**
     * @throws MigrationException|Throwable
     */
    public function down(): void
    {
        $this->executeAll([], VersionEnum::ACTION_DOWN);
    }

    /**
     * @throws MigrationException|Throwable
     */
    public function executeAll(array $filter, string $action, string $tag = ''): void
    {
        foreach ($this->versionManager->getListForExecute($filter, $action) as $version) {
            $this->executeVersion($version, $action, $tag);
        }
    }

    /**
     * @throws MigrationException|Throwable
     */
    public function executeVersion(string $version, string $action, string $tag = ''): void
    {
        $params = [];

        do {
            $result = $this->versionManager->startMigration(
                $version,
                $action,
                $params,
                $tag,
            );

            $params = $result->getRestartParams();

            if ($result->getLastException()) {
                throw $result->getLastException();
            }

        } while ($result->needRestart());
    }
}
