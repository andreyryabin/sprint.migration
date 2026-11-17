<?php

namespace Sprint\Migration\Entity;

use DateTime;
use ReflectionClass;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Module;
use Sprint\Migration\Tables\VersionTable;
use Sprint\Migration\Version;
use Sprint\Migration\VersionConfig;
use Throwable;

class VersionFileFactory
{
    /**
     * @throws MigrationException
     */
    public function create(VersionConfig $versionConfig, string $versionName): ?VersionFile
    {

        $location = $versionConfig->getVersionFile($versionName);

        $location = realpath($location);

        if (!$location || !file_exists($location)) {
            //нормальная ситуация, неизвестная миграция
            return null;
        }

        try {

            require_once($location);

            $versionClass = "\Sprint\Migration\\$versionName";

            if (!class_exists($versionClass)) {
                throw new MigrationException("Class for $versionName does not exist");
            }

            $versionObj = (new ReflectionClass($versionClass))
                ->newInstanceWithoutConstructor();

            if (!($versionObj instanceof Version)) {
                throw new MigrationException("Class $versionName not instance of Version");
            }

            $description = $this->stripslashes(
                $versionObj->getDescription()
            );

            $humanTs = DateTime::createFromFormat(
                $versionConfig->getVal('version_timestamp_format'),
                $versionConfig->getVersionTimestamp($versionName)
            );

            $createdAt = $humanTs->format(VersionTable::DATE_FORMAT);
            $createdBy = $this->stripslashes($versionObj->getAuthor());

            $v1 = $versionObj->getModuleVersion();
            $v2 = Module::getVersion();

            $olderVersion = ($v1 && version_compare($v1, $v2, '>')) ? $v1 : '';

            $algo = $versionConfig->getVal('migration_hash_algo');

            $contentHash = hash($algo, file_get_contents($location));

            return new VersionFile(
                versionName: $versionName,
                location: $location,
                versionClass: $versionClass,
                description: $description,
                olderVersion: $olderVersion,
                contentHash: $contentHash,
                createdAt: $createdAt,
                createdBy: $createdBy,
            );


        } catch (Throwable $e) {
            throw new MigrationException($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function stripslashes(string $descr = ''): string
    {
        return stripslashes(strip_tags(trim($descr)));
    }

}