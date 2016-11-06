<?php

namespace Sprint\Migration;

use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exceptions\MigrationException;

class VersionManager
{

    protected $isMssql = false;
    protected $dbName = false;

    /** @var \CDatabase */
    protected $bitrixDb = null;

    private $restarts = array();

    private $configValues = array();
    private $configName = '';

    public function __construct($configName = false) {
        $this->isMssql = ($GLOBALS['DBType'] == 'mssql');
        $this->bitrixDb = $GLOBALS['DB'];
        $this->dbName = $GLOBALS['DBName'];

        $this->loadConfig($configName);
        $this->installIfNeed();
    }

    public function startMigration($versionName, $action = 'up', $params = array(), $force = false) {
        /* @global $APPLICATION \CMain */
        global $APPLICATION;

        if (isset($this->restarts[$versionName])) {
            unset($this->restarts[$versionName]);
        }

        try {

            $action = ($action == 'up') ? 'up' : 'down';

            $meta = $this->getVersionMeta($versionName);

            if (!$meta || empty($meta['class'])) {
                throw new MigrationException('failed to initialize migration');
            }

            if (!$force) {
                if ($action == 'up' && $meta['status'] != 'new') {
                    throw new MigrationException('migration already up');
                }

                if ($action == 'down' && $meta['status'] != 'installed') {
                    throw new MigrationException('migration already down');
                }
            }

            /** @var $versionInstance Version */
            $versionInstance = new $meta['class'];
            $versionInstance->setParams($params);

            if ($action == 'up') {
                $ok = $versionInstance->up();
            } else {
                $ok = $versionInstance->down();
            }

            if ($APPLICATION->GetException()) {
                throw new MigrationException($APPLICATION->GetException()->GetString());
            }

            if ($ok === false) {
                throw new MigrationException('migration returns false');
            }

            if ($action == 'up') {
                $ok = $this->addRecord($versionName);
            } else {
                $ok = $this->removeRecord($versionName);
            }

            if ($ok === false) {
                throw new MigrationException('unable to write migration to the database');
            }

            Out::out('%s (%s) success', $versionName, $action);
            return true;

        } catch (RestartException $e) {
            $this->restarts[$versionName] = isset($versionInstance) ? $versionInstance->getParams() : array();

        } catch (MigrationException $e) {
            Out::outError('%s (%s) error: %s', $versionName, $action, $e->getMessage());

        } catch (\Exception $e) {
            Out::outError('%s (%s) error: %s', $versionName, $action, $e->getMessage());
        }

        return false;
    }


    public function needRestart($version) {
        return (isset($this->restarts[$version])) ? 1 : 0;
    }

    public function getRestartParams($version) {
        return $this->restarts[$version];
    }

    public function createVersionFile($description = '', $prefix = '') {
        $description = $this->purifyDescriptionForFile($description);
        $prefix = $this->preparePrefix($prefix);

        $originTz = date_default_timezone_get();
        date_default_timezone_set('Europe/Moscow');
        $ts = date('YmdHis');
        date_default_timezone_set($originTz);

        $versionName = $prefix . $ts;

        list($extendUse, $extendClass) = explode(' as ', $this->getConfigVal('migration_extend_class'));
        $extendUse = trim($extendUse);
        $extendClass = trim($extendClass);

        if (!empty($extendClass)) {
            $extendUse = 'use ' . $extendUse . ' as ' . $extendClass . ';' . PHP_EOL;
        } else {
            $extendClass = $extendUse;
            $extendUse = '';
        }

        $str = $this->renderFile($this->getConfigVal('migration_template'), array(
            'version' => $versionName,
            'description' => $description,
            'extendUse' => $extendUse,
            'extendClass' => $extendClass,
        ));

        $file = $this->getVersionFile($versionName);
        file_put_contents($file, $str);

        if (!is_file($file)) {
            Out::outError('%s, error: can\'t create a file "%s"', $versionName, $file);
            return false;
        }

        return $this->getVersionMeta($versionName);
    }


    public function getVersions($filter = array()) {
        $filter = array_merge(array('status' => '', 'search' => ''), $filter);

        $records = array();
        /* @var $dbres \CDBResult */
        $dbres = $this->getRecords();
        while ($aItem = $dbres->Fetch()) {
            $ts = $this->getVersionTimestamp($aItem['version']);
            if ($ts) {
                $records[$aItem['version']] = $ts;
            }
        }

        $files = array();
        /* @var $item \SplFileInfo */
        $directory = new \DirectoryIterator($this->getConfigVal('migration_dir'));
        foreach ($directory as $item) {
            if ($item->isFile()) {
                $fileName = pathinfo($item->getPathname(), PATHINFO_FILENAME);
                $ts = $this->getVersionTimestamp($fileName);
                if ($ts) {
                    $files[$fileName] = $ts;
                }
            }

        }

        $merge = array_merge($records, $files);
        if ($filter['status'] == 'installed' || $filter['status'] == 'unknown') {
            arsort($merge);
        } else {
            asort($merge);
        }

        $result = array();
        foreach ($merge as $version => $ts) {
            $isRecord = array_key_exists($version, $records);
            $isFile = array_key_exists($version, $files);

            $meta = $this->prepVersionMeta($version, $isFile, $isRecord);

            if (empty($filter['status']) || $filter['status'] == $meta['status']) {

                if (!empty($filter['search'])) {
                    $textindex = $meta['version'] . $meta['description'];
                    $searchword = $filter['search'];

                    $textindex = Locale::convertToUtf8IfNeed($textindex);
                    $searchword = Locale::convertToUtf8IfNeed($searchword);

                    $searchword = trim($searchword);

                    if (false !== mb_stripos($textindex, $searchword, null, 'utf-8')) {
                        $result[] = $meta;
                    }

                } else {
                    $result[] = $meta;
                }


            }
        }
        return $result;
    }

    protected function prepVersionMeta($versionName, $isFile, $isRecord) {
        $meta = array(
            'is_file' => $isFile,
            'is_record' => $isRecord,
            'version' => $versionName,
        );

        if ($isRecord && $isFile) {
            $meta['status'] = 'installed';
        } elseif (!$isRecord && $isFile) {
            $meta['status'] = 'new';
        } elseif ($isRecord && !$isFile) {
            $meta['status'] = 'unknown';
        } else {
            return false;
        }

        if (!$isFile) {
            return $meta;
        }

        $file = $this->getVersionFile($versionName);
        $meta['location'] = $file;

        ob_start();
        /** @noinspection PhpIncludeInspection */
        require_once($file);
        ob_end_clean();

        $class = 'Sprint\Migration\\' . $versionName;
        if (!class_exists($class)) {
            return $meta;
        }

        $descr = '';
        if (!method_exists($class, '__construct')) {
            /** @var $versionInstance Version */
            $versionInstance = new $class;
            $descr = $versionInstance->getDescription();
        } elseif (class_exists('\ReflectionClass')) {
            $reflect = new \ReflectionClass($class);
            $props = $reflect->getDefaultProperties();
            $descr = $props['description'];
        }

        $meta['class'] = $class;
        $meta['description'] = $this->purifyDescriptionForMeta($descr);

        return $meta;

    }

    public function getVersionMeta($versionName) {
        if ($this->checkVersionName($versionName)) {
            $isRecord = $this->isRecordExists($versionName);
            $isFile = $this->isFileExists($versionName);
            return $this->prepVersionMeta($versionName, $isFile, $isRecord);
        }
        return false;
    }

    protected function getVersionFile($versionName) {
        return $this->getConfigVal('migration_dir') . '/' . $versionName . '.php';
    }

    protected function isFileExists($versionName) {
        $file = $this->getVersionFile($versionName);
        return file_exists($file) ? 1 : 0;
    }

    protected function isRecordExists($versionName) {
        $record = $this->getRecordByName($versionName)->Fetch();
        return (empty($record)) ? 0 : 1;
    }


    public function checkVersionName($versionName) {
        return $this->getVersionTimestamp($versionName) ? true : false;
    }

    public function getVersionTimestamp($versionName) {
        $matches = array();
        if (preg_match('/[0-9]{14}$/', $versionName, $matches)) {
            return $matches[0];
        } else {
            return false;
        }
    }

    protected function renderFile($file, $vars = array()) {
        if (is_array($vars)) {
            extract($vars, EXTR_SKIP);
        }

        ob_start();

        if (is_file($file)) {
            /** @noinspection PhpIncludeInspection */
            include $file;
        }

        $html = ob_get_clean();

        return $html;
    }

    protected function preparePrefix($prefix = '') {
        $prefix = trim($prefix);
        $default = 'Version';

        if (empty($prefix)) {
            return $default;
        }

        $prefix = preg_replace("/[^a-z0-9_]/i", '', $prefix);
        if (empty($prefix)) {
            return $default;
        }

        if (preg_match('/^\d/', $prefix)) {
            return $default;
        }

        return $prefix;
    }

    protected function purifyDescriptionForFile($descr = '') {
        $descr = strval($descr);
        $descr = str_replace(array("\n\r", "\r\n", "\n", "\r"), ' ', $descr);
        $descr = strip_tags($descr);
        $descr = addslashes($descr);
        return $descr;
    }

    protected function purifyDescriptionForMeta($descr = '') {
        $descr = strval($descr);
        $descr = str_replace(array("\n\r", "\r\n", "\n", "\r"), ' ', $descr);
        $descr = strip_tags($descr);
        $descr = stripslashes($descr);
        return $descr;
    }



    //db section

    /**
     * @param $query
     * @param null $var1
     * @param null $var2
     * @return bool|\CDBResult
     */
    protected function query($query, $var1 = null, $var2 = null) {
        if (func_num_args() > 1) {
            $params = func_get_args();
            $query = call_user_func_array('sprintf', $params);
        }

        $search = array(
            '#TABLE1#' => $this->getConfigVal('migration_table'),
            '#DBNAME#' => $this->dbName,
        );

        if (Locale::isWin1251()) {
            $search['#CHARSET#'] = 'cp1251';
            $search['#COLLATE#'] = 'cp1251_general_ci';
        } else {
            $search['#CHARSET#'] = 'utf8';
            $search['#COLLATE#'] = 'utf8_general_ci';
        }

        $querySearch = array_keys($search);
        $queryReplace = array_values($search);

        $query = str_replace($querySearch, $queryReplace, $query);

        return $this->bitrixDb->Query($query);
    }

    /**
     * @return bool|\CDBResult
     */
    public function getRecords() {
        if ($this->isMssql) {
            return $this->query('SELECT * FROM #TABLE1#');
        } else {
            return $this->query('SELECT * FROM `#TABLE1#`');
        }
    }

    /**
     * @param $versionName
     * @return bool|\CDBResult
     */
    public function getRecordByName($versionName) {
        $versionName = $this->bitrixDb->ForSql($versionName);

        if ($this->isMssql) {
            return $this->query('SELECT * FROM #TABLE1# WHERE version = \'%s\'',
                $versionName
            );

        } else {
            return $this->query('SELECT * FROM `#TABLE1#` WHERE `version` = "%s"',
                $versionName
            );
        }
    }

    /**
     * @param $versionName
     * @return bool|\CDBResult
     */
    public function addRecord($versionName) {
        $versionName = $this->bitrixDb->ForSql($versionName);

        if ($this->isMssql) {
            return $this->query('if not exists(select version from #TABLE1# where version=\'%s\')
                    begin
                        INSERT INTO #TABLE1# (version) VALUES (\'%s\')
                    end',
                $versionName,
                $versionName
            );

        } else {
            return $this->query('INSERT IGNORE INTO `#TABLE1#` (`version`) VALUES ("%s")',
                $versionName
            );
        }

    }

    /**
     * @param $versionName
     * @return bool|\CDBResult
     */
    public function removeRecord($versionName) {
        $versionName = $this->bitrixDb->ForSql($versionName);

        if ($this->isMssql) {
            return $this->query('DELETE FROM #TABLE1# WHERE version = \'%s\'',
                $versionName
            );
        } else {
            return $this->query('DELETE FROM `#TABLE1#` WHERE `version` = "%s"',
                $versionName
            );
        }
    }

    protected function installIfNeed() {
        $opt = 'table' . $this->getConfigVal('migration_table');
        if (!Module::getDbOption($opt)) {
            $this->install();
            Module::setDbOption($opt, 1);
        }
    }

    protected function install() {
        if ($this->isMssql) {
            $this->query('if not exists (SELECT * FROM sysobjects WHERE name=\'#TABLE1#\' AND xtype=\'U\')
                begin
                    CREATE TABLE #TABLE1#
                    (id int IDENTITY (1,1) NOT NULL,
                    version varchar(255) NOT NULL,
                    PRIMARY KEY (id),
                    UNIQUE (version))
                end'
            );
        } else {
            $this->query('CREATE TABLE IF NOT EXISTS `#TABLE1#`(
              `id` MEDIUMINT NOT NULL AUTO_INCREMENT NOT NULL,
              `version` varchar(255) COLLATE #COLLATE# NOT NULL,
              PRIMARY KEY (id), UNIQUE KEY(version)
              )ENGINE=InnoDB DEFAULT CHARSET=#CHARSET# COLLATE=#COLLATE# AUTO_INCREMENT=1;'
            );
        }
    }

    //config section


    public function loadConfig($configName) {
        $loaded = 0;
        if (!empty($configName) && preg_match("/^[a-z0-9_-]*$/i", $configName)) {
            $configFile = Module::getPhpInterfaceDir() . '/migrations.' . $configName . '.php';
            if (is_file($configFile)) {
                /** @noinspection PhpIncludeInspection */
                $values = include $configFile;
                $this->configName = $configName;
                $this->configValues = $this->prepareConfig($values);
                $loaded = 1;
            }
        }

        if (!$loaded) {
            $configName = 'cfg';
            $configFile = Module::getPhpInterfaceDir() . '/migrations.' . $configName . '.php';
            if (is_file($configFile)) {
                /** @noinspection PhpIncludeInspection */
                $values = include $configFile;
                $this->configName = $configName;
                $this->configValues = $this->prepareConfig($values);
                $loaded = 1;
            }
        }

        if (!$loaded) {
            $values = array();
            $this->configName = 'default';
            $this->configValues = $this->prepareConfig($values);
            $loaded = 1;
        }

        return $loaded;
    }

    public function getConfigInfo() {
        $files = array();

        /* @var $item \SplFileInfo */
        $directory = new \DirectoryIterator(Module::getPhpInterfaceDir());
        foreach ($directory as $item) {
            if (!$item->isFile()) {
                continue;
            }

            if (!preg_match('/^migrations\.([a-z0-9_-]*)\.php$/i', $item->getFilename(), $matches)) {
                continue;
            }

            $configName = $matches[1];

            /** @noinspection PhpIncludeInspection */
            $values = include $item->getPathname();
            if (!$this->validConfig($values)) {
                continue;
            }

            if ($configName == $this->configName) {
                continue;
            }

            $values = $this->prepareConfig($values);

            $files[] = array(
                'name' => $configName,
                'title' => $values['title'],
                'values' => $values,
                'current' => 0
            );

        }

        $files[] = array(
            'name' => $this->configName,
            'title' => $this->configValues['title'],
            'values' => $this->configValues,
            'current' => 1
        );

        return $files;
    }

    protected function validConfig($values) {
        $availablekeys = array(
            'migration_template',
            'migration_table',
            'migration_extend_class',
            'migration_dir',
            'tracker_task_url',
        );

        foreach ($availablekeys as $key) {
            if (!empty($values[$key])) {
                return true;
            }
        }

        return false;
    }

    protected function prepareConfig($values = array()) {
        if (!$values['title']){
            if ($this->configName == 'cfg' || $this->configName == 'default'){
                $values['title'] = GetMessage('SPRINT_MIGRATION_DEFAULT_CONFIG');
            } else {
                $values['title'] = $this->configName;
            }
        }

        if (!$values['migration_extend_class']) {
            $values['migration_extend_class'] = 'Version';
        }

        if (!$values['migration_table']) {
            $values['migration_table'] = 'sprint_migration_versions';
        }

        if ($values['migration_template'] && is_file(Module::getDocRoot() . $values['migration_template'])) {
            $values['migration_template'] = Module::getDocRoot() . $values['migration_template'];
        } else {
            $values['migration_template'] = Module::getModuleDir() . '/templates/version.php';
        }

        if ($values['migration_dir'] && is_dir(Module::getDocRoot() . $values['migration_dir'])) {
            $values['migration_dir'] = realpath(Module::getDocRoot() . $values['migration_dir']);
        } else {
            $values['migration_dir'] = realpath(Module::getPhpInterfaceDir() . '/migrations');
            if (!is_dir($values['migration_dir'])) {
                mkdir($values['migration_dir'], BX_DIR_PERMISSIONS);
            }
        }

        if (!$values['migration_webdir']) {
            if (false === strpos($values['migration_dir'], Module::getDocRoot())) {
                $values['migration_webdir'] = substr($values['migration_dir'], Module::getDocRoot());
            }
        }


        if (!$values['tracker_task_url']) {
            $values['tracker_task_url'] = '';
        }

        return $values;
    }

    public function getConfigVal($val, $default = '') {
        return !empty($this->configValues[$val]) ? $this->configValues[$val] : $default;
    }


}
