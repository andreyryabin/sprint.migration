<?php

namespace Sprint\Migration;

class Manager
{

    private $options = array();
    private $path = '';

    private $optionsFile = '';

    public function __construct() {
        if (is_dir($_SERVER['DOCUMENT_ROOT'] . '/local/php_interface/')) {
            $this->path = '/local/php_interface/';
        } else {
            $this->path = '/bitrix/php_interface/';
        }

        $this->optionsFile = $_SERVER['DOCUMENT_ROOT'] . $this->path . 'migrations.cfg.php';
        if (is_file($this->optionsFile)){
            $this->options = include $this->optionsFile;
        }
    }

    public function getMigrationDir(){
        $dir = $this->getOption('migration_dir', '');

        if (!empty($dir) && is_dir($_SERVER['DOCUMENT_ROOT'] . $dir)){
            return $dir;
        }

        $dir = $this->path . 'migrations/';
        if (!is_dir($_SERVER['DOCUMENT_ROOT'] . $dir)){
            mkdir($_SERVER['DOCUMENT_ROOT'] . $dir , BX_DIR_PERMISSIONS);
        }
        
        return $dir;
    }

    public function setMigrationDir($dir){
        if (is_dir($_SERVER['DOCUMENT_ROOT'] . $dir)){
            $this->setOption('migration_dir', $dir);
        }
    }

    public function getVersionTemplateFile(){
        $file = $this->getOption('migration_template', '');
        if (!empty($file) && is_file($_SERVER['DOCUMENT_ROOT'] . $file)){
            return $_SERVER['DOCUMENT_ROOT'] . $file;
        } else {
            return __DIR__  . '/../../../templates/version.php';
        }
    }

    protected function getOption($name, $default){
        return (isset($this->options[$name])) ? $this->options[$name] : $default;
    }

    protected function setOption($name, $val){
        $this->options[$name] = $val;
        \file_put_contents($this->optionsFile, '<?php /* sprint.migration module config */ return ' . var_export($this->options, 1) . ';', LOCK_EX);
    }

    public function getVersions() {
        return $this->findVersions('up');
    }

    public function getVersionDescription($versionName) {
        $version = $this->initVersion($versionName);
        return ($version) ? $version->getDescription() : '';
    }

    public function executeVersion($name, $up = true) {
        $version = $this->finvVersionByName($name);
        if ($version) {
            if ($up && $version['type'] == 'is_new') {
                return $this->doVersionUp($name);
            }

            if (!$up && $version['type'] == 'is_success') {
                return $this->doVersionDown($name);
            }

        }

        return false;
    }

    public function executeMigrateUp($cnt = 0) {
        $cnt = (int)$cnt;

        $versions = $this->findVersions('up');
        $cntSuccess = 0;

        foreach ($versions as $item) {

            if ($item['type'] == 'is_new') {

                if ($this->doVersionUp($item['version'])) {
                    $cntSuccess++;
                }

                if ($cnt > 0 && $cnt == $cntSuccess) {
                    break;
                }
            }
        }

        return $cntSuccess;
    }

    public function executeMigrateDown($cnt = 0) {
        $cnt = (int)$cnt;

        $versions = $this->findVersions('down');
        $cntSuccess = 0;

        foreach ($versions as $item) {

            if ($item['type'] == 'is_success') {

                if ($this->doVersionDown($item['version'])) {
                    $cntSuccess++;
                }

                if ($cnt > 0 && $cnt == $cntSuccess) {
                    break;
                }
            }
        }

        return $cntSuccess;
    }


    public function createVersionFile($description = '') {
        $description = preg_replace("/\r\n|\r|\n/", '<br/>', $description);
        $description = strip_tags($description);

        $originTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Moscow');
        $version = 'Version' . date('YmdHis');
        date_default_timezone_set($originTz);

        $str = $this->renderVersionFile(array(
            'version' => $version,
            'description' => $description,
        ));
        $file = $this->getFileName($version);
        file_put_contents($file, $str);
        return is_file($file);
    }


    protected function doVersionUp($name) {
        if ($version = $this->initVersion($name)) {
            try {
                $ok = $version->up();
                if ($ok !== false) {
                    $this->addRecord($name);
                    return true;
                }


            } catch (\Exception $e) {
            }
        }
        return false;
    }

    protected function doVersionDown($name) {
        if ($version = $this->initVersion($name)) {
            try {
                $ok = $version->down();
                if ($ok !== false) {
                    $this->removeRecord($name);
                    return true;
                }

            } catch (\Exception $e) {
            }
        }
        return false;
    }


    protected function finvVersionByName($name) {
        $list = $this->findVersions('up');
        foreach ($list as $val) {
            if ($val['version'] == $name) {
                return $val;
            }
        }
        return false;
    }

    protected function findVersions($sort = 'up') {
        $records = $this->getRecords();
        $files = $this->getFiles();

        $merge = array_merge($records, $files);
        $merge = array_unique($merge);

        if ($sort && $sort == 'up') {
            sort($merge);
        } else {
            rsort($merge);
        }

        $result = array();
        foreach ($merge as $val) {
            $num = str_replace('Version', '', $val);
            $isRecord = in_array($val, $records);
            $isFile = in_array($val, $files);

            if ($isRecord && $isFile) {
                $type = 'is_success';
            } elseif (!$isRecord && $isFile) {
                $type = 'is_new';
            } else {
                $type = 'is_404';
            }

            $aItem = array(
                'type' => $type,
                'version' => $val,
                'number' => $num,
            );

            $result[] = $aItem;
        }

        return $result;
    }

    protected function getFiles() {
        $dir = $_SERVER['DOCUMENT_ROOT'] . $this->getMigrationDir();
        $Directory = new \DirectoryIterator($dir);
        $files = array();
        /* @var $item \SplFileInfo */
        foreach ($Directory as $item) {
            $fileName = pathinfo($item->getPathname(), PATHINFO_FILENAME);
            if ($this->checkName($fileName)) {
                $files[] = $fileName;
            }
        }

        return $files;
    }

    protected function getRecords() {
        $this->createTableIfNotExists();

        if ($this->isMssql()) {
            $dbResult = $this->getDb()->Query('select * from sprint_migration_versions');
        } else {
            $dbResult = $this->getDb()->Query('SELECT * FROM `sprint_migration_versions`');
        }


        $records = array();
        while ($aItem = $dbResult->Fetch()) {
            if ($this->checkName($aItem['version'])) {
                $records[] = $aItem['version'];
            }

        }
        return $records;
    }

    protected function addRecord($versionName) {
        if ($this->checkName($versionName)) {
            if ($this->isMssql()) {
                return $this->getDb()->Query(sprintf('if not exists(select version from sprint_migration_versions where version=\'%s\')
                    begin
                        INSERT INTO sprint_migration_versions (version) VALUES	(\'%s\')
                    end', $versionName, $versionName));

            } else {
                return $this->getDb()->Query(sprintf('INSERT IGNORE INTO `sprint_migration_versions` SET `version` = "%s"', $versionName));
            }


        }
        return false;
    }

    protected function removeRecord($versionName) {
        if ($this->checkName($versionName)) {
            if ($this->isMssql()) {
                return $this->getDb()->Query(sprintf('DELETE FROM sprint_migration_versions WHERE version = \'%s\'', $versionName));
            } else {
                return $this->getDb()->Query(sprintf('DELETE FROM `sprint_migration_versions` WHERE `version` = "%s"', $versionName));
            }
        }
        return false;
    }

    protected function createTableIfNotExists() {
        if ($this->isMssql()) {
            $this->getDb()->Query('if not exists (SELECT * FROM sysobjects WHERE name=\'sprint_migration_versions\' AND xtype=\'U\')
                begin
                    CREATE TABLE sprint_migration_versions
                    (id int IDENTITY (1,1) NOT NULL,
                    version varchar(255) NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE (version))
                end');

        } else {
            $this->getDb()->Query('CREATE TABLE IF NOT EXISTS `sprint_migration_versions`(
			  `id` MEDIUMINT NOT NULL AUTO_INCREMENT NOT NULL,
			  `version` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
			  PRIMARY KEY (id), UNIQUE KEY(version)
			)ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;');
        }
    }

    /* @return Version */
    protected function initVersion($versionName) {
        $file = false;
        if ($this->checkName($versionName)) {
            $file = $this->getFileName($versionName);
        }

        if (!$file || !file_exists($file)) {
            return false;
        }

        include_once $file;

        $class = 'Sprint\Migration\\' . $versionName;
        if (!class_exists($class)) {
            return false;
        }

        $obj = new $class;
        return $obj;
    }

    protected function getFileName($versionName) {
        return $_SERVER['DOCUMENT_ROOT'] . $this->getMigrationDir() . $versionName . '.php';
    }

    protected function checkName($versionName) {
        return preg_match('/^Version\d+$/i', $versionName);
    }

    protected function renderVersionFile($vars = array()) {
        if (is_array($vars)) {
            extract($vars, EXTR_SKIP);
        }

        ob_start();

        include($this->getVersionTemplateFile());

        $html = ob_get_clean();

        return $html;
    }

    /* @return \CDatabase */
    public function getDb() {
        return $GLOBALS['DB'];
    }

    public function isMssql() {
        return ($GLOBALS['DBType'] == 'mssql');
    }

}
