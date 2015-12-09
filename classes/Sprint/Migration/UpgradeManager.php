<?php

namespace Sprint\Migration;

class UpgradeManager
{

    protected $debug = false;

    public function __construct($debug = false){
        $this->debug = $debug;
    }
    
    public function upgradeIfNeed(){
        $version = $this->getUpgradeVersion();

        $files = $this->getFiles();

        $offset = array_search($version, $files);
        if ($offset !== false){
            $files = array_slice($files, $offset + 1);
        }

        foreach ($files as $upgradeName){
            $this->doUpgrade($upgradeName);
        }

    }

    public function upgradeReload(){
        Module::setDbOption('upgrade_version', 'unknown');
        $this->upgradeIfNeed();
    }


    public function getUpgradeVersion(){
        return Module::getDbOption('upgrade_version', 'unknown');
    }


    protected function doUpgrade($name){
        $upgradeFile = Module::getUpgradeDir() . '/' . $name . '.php';

        if (!is_file($upgradeFile)){
            return false;
        }

        /** @noinspection PhpIncludeInspection */
        require_once($upgradeFile);

        $class = 'Sprint\Migration\\' . $name;

        if (!class_exists($class)) {
            return false;
        }

        /** @var Upgrade $obj */
        $obj = new $class();
        $obj->setDebug($this->debug);
        $obj->doUpgrade();

        if ($this->debug){
            Out::out('Upgrade to version: %s', $name);
        }

        Module::setDbOption('upgrade_version', $name);

        return true;
    }

    protected function getFiles(){
        $directory = new \DirectoryIterator(Module::getUpgradeDir());

        $files = array();
        /* @var $item \SplFileInfo */
        foreach ($directory as $item) {
            $fileName = pathinfo($item->getPathname(), PATHINFO_FILENAME);
            if ($this->checkUpgradeName($fileName)) {
                $files[] = $fileName;
            }
        }

        sort($files);

        return $files;
    }

    protected function checkUpgradeName($fileName){
        return preg_match('/^Upgrade\d+$/i', $fileName);
    }

}
