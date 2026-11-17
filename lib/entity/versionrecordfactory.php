<?php

namespace Sprint\Migration\Entity;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\VersionConfig;

class VersionRecordFactory
{
    /**
     * @throws MigrationException
     */
    public function create(VersionConfig $versionConfig, array $record): ?VersionRecord
    {
        if (empty($record)) {
            //нормальная ситуация, новая миграция
            return null;
        }

        $versionConfig->tryVersionName($record['version'] ?? '');

        return new VersionRecord(
            versionName: (string)($record['version'] ?? ''),
            releaseTag: (string)($record['tag'] ?? ''),
            createdAt: (string)($record['meta']['created_at'] ?? ''),
            createdBy: (string)($record['meta']['created_by'] ?? ''),
            recordHash: (string)($record['hash'] ?? ''),
        );

    }

}