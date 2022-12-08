<?php

namespace Sprint\Migration;

use ReflectionClass;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Traits\HelperManagerTrait;

/**
 * Class Version
 *
 * @package Sprint\Migration
 */
class Version extends ExchangeEntity
{
    use HelperManagerTrait;

    /**
     * @var string
     */
    protected $description = "";
    /**
     * @var string
     */
    protected $moduleVersion = "";
    /**
     * @var array
     */
    protected $versionFilter = [];
    /**
     * @var string
     */
    protected $storageName = 'default';

    /**
     * your code for up
     *
     * @throws RestartException
     * @throws HelperException
     * @return bool
     */
    public function up()
    {
        return true;
    }

    /**
     * your code for down
     *
     * @throws RestartException
     * @throws HelperException
     * @return bool
     */
    public function down()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function isVersionEnabled()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getModuleVersion()
    {
        return $this->moduleVersion;
    }

    /**
     * @return array
     */
    public function getVersionFilter()
    {
        return $this->versionFilter;
    }

    /**
     * @param $name
     * @param $data
     *
     */
    public function saveData($name, $data)
    {
        $this->getStorageManager()->saveData($this->getClassName(), $name, $data);
    }

    /**
     * @return StorageManager
     */
    protected function getStorageManager()
    {
        return new StorageManager($this->storageName);
    }
    /**
     * @return ExchangeManager
     */
    protected function getExchangeManager()
    {
        return new ExchangeManager($this);
    }
    /**
     * @param $name
     *
     * @return mixed|string
     *
     */
    public function getSavedData($name)
    {
        return $this->getStorageManager()->getSavedData($this->getClassName(), $name);
    }

    /**
     * @param bool $name
     *
     */
    public function deleteSavedData($name = false)
    {
        $this->getStorageManager()->deleteSavedData($this->getClassName(), $name);
    }

    /**
     * @throws MigrationException
     */
    public function exitIfVersionNotInstalled($versionName)
    {
        if (class_exists($versionName)) {
            $versionName = (new ReflectionClass($versionName))->getShortName();
        }
        $ok =  (new VersionManager(
            $this->getVersionConfig()
        ))->isVersionInstalled(
            $versionName
        );

        $this->exitIf(!$ok,sprintf('Version "%s" not installed', $versionName));
    }

}



