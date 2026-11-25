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
    public function __construct(private readonly VersionConfig $versionConfig) {}

    public function tryCreate(string $versionName): ?VersionFile
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
    public function create(string $versionName): ?VersionFile
    {
        $location = $this->versionConfig->getVersionFile($versionName);

        $versionTimestamp = $this->versionConfig->getVersionTimestamp($versionName);
        $timestampFormat = $this->versionConfig->getVal('version_timestamp_format');
        $algo = $this->versionConfig->getVal('migration_hash_algo');

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

            $description = $this->stripslashes($versionObj->getDescription());

            $createdAt = DateTime::createFromFormat(
                $timestampFormat,
                $versionTimestamp,
            )->format(VersionTable::DATE_FORMAT);

            $createdBy = $this->stripslashes($versionObj->getAuthor());

            $v1 = $versionObj->getModuleVersion();
            $v2 = Module::getVersion();

            $olderVersion = ($v1 && version_compare($v1, $v2, '>')) ? $v1 : '';
            $contentHash = hash($algo, file_get_contents($location));

            return new VersionFile(
                versionName: $versionName,
                versionTimestamp: $versionTimestamp,
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