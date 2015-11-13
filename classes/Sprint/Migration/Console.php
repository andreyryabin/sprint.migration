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
            Out::out('Command %s not found, see help', $method);
            return false;
        }

        call_user_func_array(array($this, $method), $args);
    }

    public function commandCreate($descr = '') {
        $result = $this->versionManager->createVersionFile($descr);
        if (!empty($result['version'])){
            Out::out('Created: %s', $result['version']);
        }
        if (!empty($result['description'])){
            Out::out('Description: %s', $result['description']);
        }
        if (!empty($result['location'])){
            Out::out('Location: %s', $result['location']);
        }
    }

    public function commandList() {
        $versions = $this->versionManager->getVersions('all');

        $titles = array(
            'is_new' => '(new)',
            'is_success' => '',
            'is_unknown' => '(unknown)',
        );

        foreach ($versions as $aItem) {
            Out::out('%s %s', $aItem['version'], $titles[$aItem['type']]);
        }
    }

    public function commandStatus($version = '') {
        if ($version){
            $titles = array(
                'is_new' =>     'New migration',
                'is_success' => 'Success',
                'is_unknown' => 'Unknown',
            );

            $descr = $this->versionManager->getVersionDescription($version);

            $type = $this->versionManager->getVersionType($version);
            if ($type){
                if (!empty($titles[$type])){
                    Out::out('Type: %s', $titles[$type]);
                }
                if (!empty($descr['description'])){
                    Out::out('Description: %s', $descr['description']);
                }
                if (!empty($descr['location'])){
                    Out::out('Location: %s', $descr['location']);
                }
            } else {
                Out::out('%s not found!');
            }

        } else {
            $status = $this->versionManager->getStatus();

            $titles = array(
                'is_new' =>     'New migrations',
                'is_success' => 'Success',
                'is_unknown' => 'Unknown',
            );

            foreach ($status as $type => $cnt) {
                Out::out('%s: %d', $titles[$type], $cnt);
            }
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

    public function commandUp($limit = 1) {
        $limit = (int)$limit;
        if ($limit > 0) {
            $this->executeAll('up', $limit);
        } else {
            $this->outParamsError();
        }
    }

    public function commandDown($limit = 1) {
        $limit = (int)$limit;
        if ($limit > 0) {
            $this->executeAll('down', $limit);
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
        Out::out('Migrations:'.PHP_EOL.'   %s'.PHP_EOL, Env::getMigrationDir());
        Out::out('Usage:'.PHP_EOL.'   php %s <command> [<args>]'.PHP_EOL, $this->script);

        $cmd = Env::getModuleDir() . '/commands.txt';

        if (is_file($cmd)){

            $msg = file_get_contents($cmd);
            if (Env::isWin1251()){
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

        Out::out('migrations %s: %d', $action, $success);

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
