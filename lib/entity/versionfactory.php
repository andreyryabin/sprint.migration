<?php

namespace Sprint\Migration\Entity;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\VersionConfig;

class VersionFactory
{
    private readonly VersionFileFactory $fileFactory;
    private readonly VersionRecordFactory $recordFactory;
    private readonly VersionExchangeFactory $exchangeFactory;

    public function __construct(
        private readonly VersionConfig $versionConfig,
    ) {
        $this->fileFactory = new VersionFileFactory($this->versionConfig);
        $this->recordFactory = new VersionRecordFactory($this->versionConfig);
        $this->exchangeFactory = new VersionExchangeFactory($this->versionConfig);
    }

    /**
     * @throws MigrationException
     */
    public function createFromName(string $versionName, array $versionRecord = []): VersionEntity
    {
        $this->versionConfig->checkVersionName($versionName);

        return new VersionEntity(
            $this->fileFactory->create($versionName),
            $this->recordFactory->create($versionRecord),
            $this->exchangeFactory->create($versionName),
        );
    }

    /**
     * @throws MigrationException
     */
    public function createFromObjects(?VersionFile $versionFile, ?VersionRecord $versionRecord): VersionEntity
    {
        $versionName = $versionFile?->getVersionName() ?? $versionRecord?->getVersionName();

        return new VersionEntity(
            $versionFile,
            $versionRecord,
            $this->exchangeFactory->create($versionName),
        );
    }
}