<?php

namespace Sprint\Migration;

class Console
{

    protected $versionManager = null;

    protected $script = 'migrate.php';

    protected $arguments = array();

    public function __construct() {
        $this->versionManager = new VersionManager();
    }

    protected function initializeArgs($args){
        $this->arguments = array();
        foreach ($args as $val){
            $this->addArg($val);
        }
    }

    protected function addArg($arg){
        list($name, $val) = explode('=', $arg);
        $isparam = (0 === strpos($name, '--')) ? 1 : 0;
        if ($isparam){
            $val = is_null($val) ? 1 : $val;
            $this->arguments[$name] = $val;
        } else {
            $this->arguments[] = $name;
        }
    }

    protected function getArg($name, $default = ''){
        return isset($this->arguments[$name]) ? $this->arguments[$name] : $default;
    }

    public function executeConsoleCommand($args) {
        $this->script = array_shift($args);

        if (empty($args) || count($args) <= 0) {
            $this->commandHelp();
            return false;
        }

        $method = array_shift($args);

        $method = str_replace(array('_', '-', ' '), '*', $method);
        $method = explode('*', $method);
        $tmp = array();
        foreach ($method as $val) {
            $tmp[] = ucfirst(strtolower($val));
        }

        $method = 'command' . implode('', $tmp);

        if (!method_exists($this, $method)) {
            Out::out('Command not found, see help');
            return false;
        }

        $this->initializeArgs($args);

        call_user_func(array($this, $method));
        return true;
    }


    public function commandAdd() {
        $this->commandCreate();
    }

    public function commandCreate() {
        $descr = $this->getArg(0, '');
        $name = $this->getArg(1, '');

        $descr = $this->getArg('--desc', $descr);
        $name = $this->getArg('--name', $name);

        $meta = $this->versionManager->createVersionFile($descr, $name);
        $this->outVersionMeta($meta);
    }

    public function commandLs() {
        $this->commandList();
    }

    public function commandList() {
        $search = $this->getArg('--search');
        $for = ($this->getArg('--new')) ? 'up' : 'all';

        $versions = $this->versionManager->getVersions(array(
            'for' => $for,
            'search' => $search
        ));

        Out::initTable(array(
            'Version',
            'Status',
            'Description',
            //'Location'
        ));
        foreach ($versions as $aItem){
            Out::addTableRow(array(
                $aItem['version'],
                GetMessage('SPRINT_MIGRATION_CON_' . strtoupper($aItem['status'])),
                $aItem['description'],
                //$aItem['location'],
            ));
        }

        Out::outTable();
    }

    public function commandSt() {
        $this->commandStatus();
    }
    public function commandStatus() {
        $version = $this->getArg(0, '');

        if ($version){
            $meta = $this->versionManager->getVersionMeta($version);
            $this->outVersionMeta($meta);

        } else {
            $search = $this->getArg('--search');
            $versions = $this->versionManager->getVersions(array(
                'for' => 'all',
                'search' => $search
            ));

            $status = array(
                'is_new' => 0,
                'is_installed' => 0,
                'is_unknown' => 0,
            );

            foreach ($versions as $aItem) {
                $key = $aItem['status'];
                $status[$key]++;
            }

            Out::initTable(array('Status', 'Count'));
            foreach ($status as $k => $v){
                Out::addTableRow(array(GetMessage('SPRINT_MIGRATION_CON_' . strtoupper($k)), $v));
            }
            Out::outTable();
        }
    }

    public function commandMigrate() {
        $force = $this->getArg('--force');

        $search = $this->getArg('--search');
        $for = ($this->getArg('--down')) ? 'down' : 'up';

        $this->executeAll(array(
            'for' => $for,
            'search' => $search
        ), 0, $force);
    }

    public function commandUp() {
        $force = $this->getArg('--force');
        $var = $this->getArg(0, 1);

        $search = $this->getArg('--search');
        $for = 'up';

        if ($this->versionManager->checkVersionName($var)){
            $this->executeOnce($var, 'up',$force);

        } elseif ($this->getArg('--all')){
            $this->executeAll(array(
                'for' => $for,
                'search' => $search
            ), 0, $force);

        } else {
            $this->executeAll(array(
                'for' => $for,
                'search' => $search
            ), intval($var), $force);
        }

    }

    public function commandDown() {
        $force = $this->getArg('--force');
        $var = $this->getArg(0, 1);

        $search = $this->getArg('--search');
        $for = 'down';

        if ($this->versionManager->checkVersionName($var)){
            $this->executeOnce($var, 'down',$force);

        } elseif ($this->getArg('--all')){
            $this->executeAll(array(
                'for' => $for,
                'search' => $search
            ), 0, $force);

        } else {
            $this->executeAll(array(
                'for' => $for,
                'search' => $search
            ), intval($var), $force);
        }

    }

    public function commandExecute() {
        $version = $this->getArg(0, '');
        $force = $this->getArg('--force');

        if ($version){
            if ($this->getArg('--down')){
                $this->executeOnce($version, 'down', $force);
            } else {
                $this->executeOnce($version, 'up', $force);
            }
        } else {
            $this->outParamsError();
        }

    }

    public function commandRedo() {
        $version = $this->getArg(0, '');
        $force = $this->getArg('--force');

        if ($version) {
            $this->executeOnce($version, 'down', $force);
            $this->executeOnce($version, 'up', $force);
        } else {
            $this->outParamsError();
        }
    }

    /** @deprecated use flag --force */
    public function commandForce() {
        Out::out('deprecated! use flag --force');
        $this->addArg('--force');
        $this->commandExecute();
    }

    public function commandHelp() {
        Out::out(GetMessage('SPRINT_MIGRATION_MODULE_NAME'));
        Out::out('Версия bitrix: %s', defined('SM_VERSION') ? SM_VERSION : '');
        Out::out('Версия модуля: %s', Module::getVersion());
        Out::out('');
        Out::out('Директория с миграциями:'.PHP_EOL.'   %s'.PHP_EOL, Module::getMigrationDir());
        Out::out('Запуск:'.PHP_EOL.'   php %s <command> [<args>]'.PHP_EOL, $this->script);
        Out::out(file_get_contents(Module::getModuleDir() . '/commands.txt'));
    }

    protected function executeAll($filter, $limit = 0, $force = false) {
        $limit = (int)$limit;

        $success = 0;

        $versions = $this->versionManager->getVersions($filter);

        $method = ($filter['for'] == 'up') ? 'up' : 'down';

        foreach ($versions as $aItem) {
            if ($this->executeOnce($aItem['version'], $method, $force)) {
                $success++;
            }

            if ($limit > 0 && $limit == $success) {
                break;
            }
        }

        Out::out('migrations (%s): %d', $filter['for'], $success);
        return $success;
    }

    protected function executeOnce($version, $method = 'up', $force = false) {
        $params = array();
        $restart = 0;

        if ($force){
            $this->versionManager->checkPermissions(0);
        }

        do {
            $exec = 0;
            $ok = $this->versionManager->startMigration($version, $method, $params, $restart);
            if ($this->versionManager->needRestart($version)) {
                $params = $this->versionManager->getRestartParams($version);
                $restart = 1;
                $exec = 1;
            }

        } while ($exec == 1);

        return $ok;
    }

    protected function outParamsError(){
        Out::out('Required params not found, see help');
    }

    protected function outVersionMeta($meta = false){
        if ($meta) {
            Out::initTable();
            foreach (array('version', 'status', 'description', 'location') as $val){
                if (!empty($meta[$val])){
                    if ($val == 'status'){
                        Out::addTableRow(array(ucfirst($val), GetMessage('SPRINT_MIGRATION_CON_' . strtoupper($meta[$val]))));
                    } else {
                        Out::addTableRow(array(ucfirst($val), $meta[$val]));
                    }
                }
            }
            Out::outTable();
        } else {
            Out::out('Version not found!');
        }
    }
}
