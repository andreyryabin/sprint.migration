<?php

namespace Sprint\Migration;

class Console
{

    protected $versionManager = null;

    protected $script = 'migrate.php';

    public function __construct() {
        $this->versionManager = new VersionManager();
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

        call_user_func_array(array($this, $method), $args);
        return true;
    }

    public function commandCreate($descr = '', $prefix = '') {
        $meta = $this->versionManager->createVersionFile($descr, $prefix);
        $this->outVersionMeta($meta);
    }

    public function commandList() {
        $versions = $this->versionManager->getVersions(array(
            'for' => 'all'
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
                $this->getStatusTitle($aItem['status']),
                $aItem['description'],
                //$aItem['location'],
            ));
        }

        Out::outTable();
    }

    public function commandStatus($version = '') {
        if ($version){
            $meta = $this->versionManager->getVersionMeta($version);
            $this->outVersionMeta($meta);
        } else {

            $versions = $this->versionManager->getVersions(array(
                'for' => 'all',
                'desc' => '',
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
                Out::addTableRow(array($this->getStatusTitle($k), $v));
            }
            Out::outTable();
        }
    }

    public function commandMigrate($up = '--up') {
        if ($up == '--up') {
            $this->executeAll('up');

        } elseif ($up == '--down') {
            $this->executeAll('down');

        } else {
            $this->outParamsError();
        }
    }

    public function commandUp($var = 1) {
        if ($this->versionManager->checkVersionName($var)){
            $this->executeOnce($var, 'up');
        } elseif ($var == '--all') {
            $this->executeAll('up');
        } elseif (is_numeric($var) && intval($var) > 0){
            $this->executeAll('up', intval($var));
        } else {
            $this->outParamsError();
        }
    }

    public function commandDown($var = 1) {
        if ($this->versionManager->checkVersionName($var)){
            $this->executeOnce($var, 'down');
        } elseif ($var == '--all') {
            $this->executeAll('down');
        } elseif (is_numeric($var) && intval($var) > 0){
            $this->executeAll('down', intval($var));
        } else {
            $this->outParamsError();
        }
    }

    public function commandExecute($version = '', $up = '--up') {
        if ($version && $up == '--up') {
            $this->executeOnce($version, 'up');

        } elseif ($version && $up == '--down') {
            $this->executeOnce($version, 'down');

        } else {
            $this->outParamsError();
        }
    }

    public function commandRedo($version = '') {
        if ($version) {
            $this->executeOnce($version, 'down');
            $this->executeOnce($version, 'up');
        } else {
            $this->outParamsError();
        }
    }

    public function commandForce($version = '', $up = '--up') {
        $this->versionManager->checkPermissions(0);
        $this->commandExecute($version, $up);
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

    protected function executeAll($action = 'up', $limit = 0) {
        $action = ($action == 'up') ? 'up' : 'down';
        $limit = (int)$limit;

        $success = 0;

        $versions = $this->versionManager->getVersions(array(
            'for' => $action
        ));
        foreach ($versions as $aItem) {
            if ($this->executeOnce($aItem['version'], $action)) {
                $success++;
            }

            if ($limit > 0 && $limit == $success) {
                break;
            }
        }

        Out::out('migrations (%s): %d', $action, $success);
        return $success;
    }

    protected function executeOnce($version, $action = 'up') {
        $action = ($action == 'up') ? 'up' : 'down';
        $params = array();
        $restart = 0;

        do {
            $exec = 0;
            $ok = $this->versionManager->startMigration($version, $action, $params, $restart);
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
                        Out::addTableRow(array(ucfirst($val), $this->getStatusTitle($meta[$val])));
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

    protected function getStatusTitle($code){
        $titles = array(
            'is_new' => 'New',
            'is_installed' => 'Installed',
            'is_unknown' => 'Unknown',
        );

        return isset($titles[$code]) ? $titles[$code] : $code;
    }
}
