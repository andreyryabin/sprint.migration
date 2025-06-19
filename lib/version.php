<?php

namespace Sprint\Migration;

use ReflectionClass;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exchange\ExchangeManager;
use Sprint\Migration\Interfaces\RestartableInterface;
use Sprint\Migration\Traits\HelperManagerTrait;
use Sprint\Migration\Traits\OutTrait;
use Sprint\Migration\Traits\RestartableTrait;
use Sprint\Migration\Traits\VersionConfigTrait;

class Version implements RestartableInterface
{
    use HelperManagerTrait;
    use OutTrait;
    use RestartableTrait;
    use VersionConfigTrait;

    protected $author        = "";
    protected $description   = "";
    protected $moduleVersion = "";
    /**
     * @deprecated Используете $this->checkRequiredVersions(['Version1','Version1'])
     */
    protected $requiredVersions = [];

    /**
     * @throws MigrationException
     */
    public function up()
    {
        throw new MigrationException(Locale::getMessage('WRITE_UP_CODE'));
    }

    /**
     * @throws MigrationException
     */
    public function down()
    {
        throw new MigrationException(Locale::getMessage('WRITE_DOWN_CODE'));
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function getModuleVersion(): string
    {
        return $this->moduleVersion;
    }

    /**
     * @deprecated
     */
    public function getRequiredVersions(): array
    {
        return $this->requiredVersions;
    }

    public function getVersionName(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }

    /**
     * Метод проверяет установлены ли обязательные миграции и бросает исключение если нет
     * $versionNames = ['Version1','Version1'] или [Version1::class,Version2::class]
     *
     * @throws MigrationException
     */
    public function checkRequiredVersions($versionNames): void
    {
        (new VersionManager(
            $this->getVersionConfig()
        ))->checkRequiredVersions($versionNames);
    }

    /**
     * @throws MigrationException
     */
    protected function getStorageManager($versionName = ''): StorageManager
    {
        if (empty($versionName)) {
            $versionName = $this->getVersionName();
        }

        return new StorageManager('sprint_storage_default', $versionName);
    }

    protected function getExchangeManager(): ExchangeManager
    {
        $dir = $this->getVersionConfig()->getVersionExchangeDir(
            $this->getVersionName()
        );

        return new ExchangeManager($this, $dir);
    }
}



