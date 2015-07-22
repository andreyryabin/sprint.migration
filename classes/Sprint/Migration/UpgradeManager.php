<?php

namespace Sprint\Migration;

class UpgradeManager
{

    public function __construct(){
        
    }
    
    public function upgradeIfNeed(){
        $version = Env::getDbOption('upgrade_version', 'unknown');

        $files = $this->getFiles();

        $offset = array_search($version, $files);
        if ($offset !== false){
            $files = array_slice($files, $offset + 1);
        }

        foreach ($files as $upgradeName){
            $this->doUpgrade($upgradeName);
        }

    }

    protected function doUpgrade($name){
        $upgradeFile = Env::getUpgradeDir() . '/' . $name . '.php';

        if (!is_file($upgradeFile)){
            return false;
        }

        require_once($upgradeFile);

        $class = 'Sprint\Migration\\' . $name;

        if (!class_exists($class)) {
            return false;
        }

        /** @var Upgrade $obj */
        $obj = new $class;
        $obj->doUpgrade();

        Env::setDbOption('upgrade_version', $name);

        return true;
    }

    protected function getFiles(){
        $directory = new \DirectoryIterator(Env::getUpgradeDir());

        $files = array();
        /* @var $item \SplFileInfo */
        foreach ($directory as $item) {
            $fileName = pathinfo($item->getPathname(), PATHINFO_FILENAME);
            if ($this->checkName($fileName)) {
                $files[] = $fileName;
            }
        }

        sort($files);

        return $files;
    }

    protected function checkName($fileName){
        return preg_match('/^Upgrade\d+$/i', $fileName);
    }

}
