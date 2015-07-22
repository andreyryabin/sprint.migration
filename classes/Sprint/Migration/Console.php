<?php

namespace Sprint\Migration;

class Console
{

    protected $manager = null;

    public function __construct() {
        $this->manager = new Manager();
    }


    public function executeConsoleCommand($args) {
        $script = array_shift($args);

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
        $this->manager->createMigrationFile($descr);
    }

    public function commandList() {
        $versions = $this->manager->getVersions('all');

        $titles = array(
            'is_new' => '(new)',
            'is_success' => '',
            'is_unknown' => '(unknown)',
        );

        foreach ($versions as $aItem) {
            Out::out('%s %s', $aItem['version'], $titles[$aItem['type']]);
        }
    }

    public function commandStatus() {
        $summ = $this->manager->getSummaryVersions();

        $titles = array(
            'is_new' =>     'new migrations',
            'is_success' => 'success',
            'is_unknown' => 'unknown',
        );

        foreach ($summ as $type => $cnt) {
            Out::out('%s: %d', $titles[$type], $cnt);
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

    public function commandInfo($version = '') {
        if ($version){

            if ($this->manager->canEdit($version)){
                $descr = $this->manager->getDescription($version);
                if ($descr){
                    Out::out($descr);
                } else {
                    Out::outError('%s error: empty description', $version);
                }

            } else {
                Out::outError('%s error: file not found', $version);
            }

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

    public function commandExecuteForce($version = '', $up = '--up') {
        $this->manager->enableForce();
        $this->commandExecute($version, $up);
    }

    public function commandHelp() {
        $cmd = Env::getModuleDir() . '/commands.txt';
        if (is_file($cmd)){
            Out::out(file_get_contents($cmd));
        }
    }

    protected function executeAll($action = 'up', $limit = 0) {
        $action = ($action == 'up') ? 'up' : 'down';
        $limit = (int)$limit;

        $success = 0;

        $versions = $this->manager->getVersions($action);
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
            $ok = $this->manager->startMigration($version, $action, $params);
            if ($this->manager->needRestart($version)) {
                $params = $this->manager->getRestartParams($version);
                $restart = 1;
            }

        } while ($restart == 1);

        return $ok;
    }

    protected function outParamsError(){
        Out::out('Required params not found, see help');
    }
}
