<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Traits\ExitMessageTrait;
use Sprint\Migration\Traits\HelperManagerTrait;
use Sprint\Migration\Traits\OutTrait;
/**
 * Class Version
 *
 * @package Sprint\Migration
 */
class Version extends ExchangeEntity
{
    use HelperManagerTrait;
    use ExitMessageTrait;
    use OutTrait;

    protected $author        = "";
    protected $description   = "";
    protected $moduleVersion = "";
    /**
     * Миграции, которые должны быть установлены перед установкой текущей
     * $this->requiredVersions = ['Version1','Version1']
     * или
     * $this->requiredVersions = [Version1::class,Version2::class]
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

    public function getRequiredVersions(): array
    {
        return $this->requiredVersions;
    }

    /**
     * @throws MigrationException
     */
    public function checkRequiredVersions($versionNames)
    {
        (new VersionManager($this->getVersionConfig()))->checkRequiredVersions($versionNames);
    }

    /**
     * @throws MigrationException
     */
    protected function getStorageManager($versionName = ''): StorageManager
    {
        if (empty($versionName)) {
            $versionName = $this->getClassName();
        }

        return new StorageManager('sprint_storage_default', $versionName);
    }

    protected function getExchangeManager(): ExchangeManager
    {
        return new ExchangeManager($this);
    }
}



