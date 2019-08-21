<?php

namespace Sprint\Migration;

/**
 * Class Version
 * @package Sprint\Migration
 */
class Version extends ExchangeEntity
{

    /**
     * @var string
     */
    protected $description = "";

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
     * @return bool
     */
    public function up()
    {
        return true;
    }

    /**
     * your code for down
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
    public function getVersionName()
    {
        return $this->getClassName();
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
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
     */
    public function saveData($name, $data)
    {
        $storage = new StorageManager($this->storageName);
        $storage->saveData($this->getVersionName(), $name, $data);
    }

    /**
     * @param $name
     * @return mixed|string
     *
     */
    public function getSavedData($name)
    {
        $storage = new StorageManager($this->storageName);
        return $storage->getSavedData($this->getVersionName(), $name);
    }

    /**
     * @param bool $name
     */
    public function deleteSavedData($name = false)
    {
        $storage = new StorageManager($this->storageName);
        $storage->deleteSavedData($this->getVersionName(), $name);
    }

    /**
     * @return ExchangeManager
     */
    protected function getExchangeManager()
    {
        return new ExchangeManager($this);
    }

    /**
     * @return HelperManager
     */
    protected function getHelperManager()
    {
        return HelperManager::getInstance();
    }
}



