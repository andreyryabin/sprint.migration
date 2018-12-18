<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exceptions\MigrationException;

class Version
{

    use OutTrait;

    protected $description = "";
    protected $versionFilter = array();

    protected $params = array();

    protected $storageName = 'default';


    public function up() {
        return true;
    }

    public function down() {
        return true;
    }

    public function isVersionEnabled() {
        return true;
    }

    public function getVersionName() {
        $path = explode('\\', get_class($this));
        return array_pop($path);
    }

    public function getDescription() {
        return $this->description;
    }

    public function getVersionFilter() {
        return $this->versionFilter;
    }

    public function saveData($name, $data) {
        $storage = new StorageManager($this->storageName);
        $storage->saveData($this->getVersionName(), $name, $data);
    }

    public function getSavedData($name) {
        $storage = new StorageManager($this->storageName);
        return $storage->getSavedData($this->getVersionName(), $name);
    }

    public function deleteSavedData($name = false) {
        $storage = new StorageManager($this->storageName);
        $storage->deleteSavedData($this->getVersionName(), $name);
    }

    public function restart() {
        Throw new RestartException();
    }

    /* Need For Sprint\Migration\VersionManager */
    public function getParams() {
        return $this->params;
    }

    /* Need For Sprint\Migration\VersionManager */
    public function setParams($params = array()) {
        $this->params = $params;
    }

    public function exitIf($cond, $msg) {
        if ($cond) {
            Throw new MigrationException($msg);
        }
    }

    public function exitIfEmpty($var, $msg) {
        if (empty($var)) {
            Throw new MigrationException($msg);
        }
    }

}



