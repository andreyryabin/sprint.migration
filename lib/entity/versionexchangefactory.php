<?php

namespace Sprint\Migration\Entity;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\VersionConfig;

class VersionExchangeFactory
{
    public function __construct(private readonly VersionConfig $versionConfig) {}

    public function tryCreate(string $versionName): ?VersionExchange
    {
        try {
            return $this->create($versionName);
        } catch (MigrationException) {
            return null;
        }
    }

    /**
     * @throws MigrationException
     */
    public function create(string $versionName): ?VersionExchange
    {
        $dir = $this->versionConfig->getVersionExchangeDir($versionName);

        return new VersionExchange(
            directoryPath:   $dir,
            directoryExists: $dir && is_dir($dir),
        );
    }

}