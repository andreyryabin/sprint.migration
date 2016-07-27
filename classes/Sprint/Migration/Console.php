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
        $result = $this->versionManager->createVersionFile($descr, $prefix);

        $type = $this->versionManager->getVersionType($result['version']);

        Out::initTable();
        if (!empty($result['version'])){
            Out::addTableRow(array('Version', $result['version']));
        }

        $titles = array(
            'is_new' => 'new',
            'is_installed' => 'installed',
            'is_unknown' => 'unknown',
        );

        if ($type){
            if (!empty($titles[$type])){
                Out::addTableRow(array('Status', $titles[$type]));
            }
        }

        if (!empty($result['description'])){
            Out::addTableRow(array('Description', $result['description']));
        }
        if (!empty($result['location'])){
            Out::addTableRow(array('Location', $result['location']));
        }

        Out::outTable();
    }

    public function commandList() {
        $versions = $this->versionManager->getVersions('all');

        $titles = array(
            'is_new' => 'new',
            'is_installed' => 'installed',
            'is_unknown' => 'unknown',
        );

        Out::initTable(array('Version', 'Status', 'Location'));
        foreach ($versions as $aItem){
            $type = $aItem['type'];
            $file = ($type != 'is_unknown') ? $this->versionManager->getVersionFile($aItem['version']) : '';

            Out::addTableRow(array($aItem['version'], $titles[$type], $file));
        }

        Out::outTable();
    }

    protected function outVersionStatus($version){
        $descr = $this->versionManager->getVersionDescription($version);
        $type = $this->versionManager->getVersionType($version);

        $titles = array(
            'is_new' => 'new',
            'is_installed' => 'installed',
            'is_unknown' => 'unknown',
        );

        if ($type){
            Out::initTable();
            Out::addTableRow(array('Version', $version));

            if (!empty($titles[$type])){
                Out::addTableRow(array('Status', $titles[$type]));
            }
            if (!empty($descr['description'])){
                Out::addTableRow(array('Description', $descr['description']));
            }
            if (!empty($descr['location'])){
                Out::addTableRow(array('Location', $descr['location']));
            }
            Out::outTable();

        } else {
            Out::out('%s not found!', $version);
        }
    }

    protected function outSummaryStatus(){
        $status = $this->versionManager->getStatus();

        Out::initTable(array('Status', 'Count'));

        Out::addTableRow(array('new', $status['is_new']));
        Out::addTableRow(array('installed', $status['is_installed']));
        Out::addTableRow(array('unknown',$status['is_unknown']));

        Out::outTable();
    }

    public function commandStatus($version = '') {
        if ($version){
            $this->outVersionStatus($version);
        } else {
            $this->outSummaryStatus();
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
        Out::out('Директория с миграциями:'.PHP_EOL.'   %s'.PHP_EOL, Module::getMigrationDir());
        Out::out('Запуск:'.PHP_EOL.'   php %s <command> [<args>]'.PHP_EOL, $this->script);

        $cmd = Module::getModuleDir() . '/commands.txt';

        if (is_file($cmd)){
            $msg = file_get_contents($cmd);
            if (Module::isWin1251()){
                $msg = iconv('utf-8', 'windows-1251//IGNORE', $msg);
            }

            Out::out($msg);
        }
    }

    protected function executeAll($action = 'up', $limit = 0) {
        $action = ($action == 'up') ? 'up' : 'down';
        $limit = (int)$limit;

        $success = 0;

        $versions = $this->versionManager->getVersions($action);
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

        do {
            $restart = 0;
            $ok = $this->versionManager->startMigration($version, $action, $params);
            if ($this->versionManager->needRestart($version)) {
                $params = $this->versionManager->getRestartParams($version);
                $restart = 1;
            }

        } while ($restart == 1);

        return $ok;
    }

    protected function outParamsError(){
        Out::out('Required params not found, see help');
    }
}
