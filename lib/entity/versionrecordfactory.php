<?php

namespace Sprint\Migration\Entity;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\VersionConfig;

class VersionRecordFactory
{
    public function __construct(private readonly VersionConfig $versionConfig) {}

    public function tryCreate(array $versionRecord): ?VersionRecord
    {
        try {
            return $this->create($versionRecord);
        } catch (MigrationException) {
            return null;
        }
    }

    /**
     * @throws MigrationException
     */
    public function create(array $versionRecord): ?VersionRecord
    {
        if (empty($versionRecord['version'])) {
            //нормальная ситуация, новая миграция
            return null;
        }

        $this->versionConfig->tryVersionName($versionRecord['version']);

        $versionTimestamp = $this->versionConfig->getVersionTimestamp($versionRecord['version']);

        return new VersionRecord(
            versionName: (string) ($versionRecord['version']),
            versionTimestamp: $versionTimestamp,
            releaseTag: (string) ($versionRecord['tag'] ?? ''),
            createdAt: (string) ($versionRecord['meta']['created_at'] ?? ''),
            createdBy: (string) ($versionRecord['meta']['created_by'] ?? ''),
            recordHash: (string) ($versionRecord['hash'] ?? ''),
        );
    }

}