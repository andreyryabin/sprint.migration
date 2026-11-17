<?php

namespace Sprint\Migration;

use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Output\OutputInterface;

class Installer
{
    private VersionManager $versionManager;


    /**
     * @throws MigrationException
     */
    public function __construct(
        array            $configValues = [],
        ?OutputInterface $output = null,
    )
    {
        $this->versionManager = new VersionManager(
            new VersionConfig(VersionEnum::CONFIG_INSTALLER, $configValues),
            $output
        );
    }

    /**
     * @throws MigrationException
     */
    public function up(): void
    {
        $this->versionManager->executeAll([], VersionEnum::ACTION_UP);
    }

    /**
     * @throws MigrationException
     */
    public function down(): void
    {
        $this->versionManager->executeAll([], VersionEnum::ACTION_DOWN);
    }

    /**
     * @throws MigrationException
     */
    public function executeAll(array $filter, string $action, string $tag = ''): void
    {
        $this->versionManager->executeAll($filter, $action, $tag);
    }


    /**
     * @throws MigrationException
     */
    public function executeVersion(string $version, string $action, string $tag = ''): bool
    {
        return $this->versionManager->executeVersion($version, $action, $tag);
    }
}
