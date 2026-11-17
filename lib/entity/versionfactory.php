<?php

namespace Sprint\Migration\Entity;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\VersionConfig;

class VersionFactory
{

    public function __construct(
        private readonly VersionConfig $versionConfig
    )
    {
    }

    /**
     * @throws MigrationException
     */
    public function create(string $versionName, array $versionRecord = []): VersionEntity
    {
        $this->versionConfig->tryVersionName($versionName);

        return new VersionEntity(
            (new VersionFileFactory($this->versionConfig))->create($versionName),
            (new VersionRecordFactory($this->versionConfig))->create($versionRecord),
            (new VersionExchangeFactory($this->versionConfig))->create($versionName),
        );
    }
}