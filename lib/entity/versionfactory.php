<?php

namespace Sprint\Migration\Entity;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\VersionConfig;

class VersionFactory
{

    /**
     * @throws MigrationException
     */
    public function create(
        VersionConfig $versionConfig,
        string        $versionName,
        array         $record = []
    ): VersionEntity
    {
        $versionConfig->tryVersionName($versionName);

        $exchangeDir = $versionConfig->getVersionExchangeDir($versionName);
        $hasExchangeDir = $exchangeDir && is_dir($exchangeDir);

        return new VersionEntity(
            (new VersionFileFactory())->create($versionConfig, $versionName),
            (new VersionRecordFactory())->create($versionConfig, $record),
            $versionName,
            $exchangeDir,
            $hasExchangeDir,
        );
    }

}