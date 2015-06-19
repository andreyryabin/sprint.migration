<?php

namespace Sprint\Migration;

class Console
{

    protected $manager = null;

    public function __construct() {
        $this->manager = new Manager();
    }

    public function execFromArgs($args) {
        $script = array_shift($args);

        if (empty($args) || count($args) <= 0) {
            $this->executeHelp();
            return false;
        }

        $method = array_shift($args);
        $method = 'execute' . $this->camelizeText($method);

        if (!method_exists($this, $method)) {
            Out::out('command %s not found', $method);
            return false;
        }

        call_user_func_array(array($this, $method), $args);
    }

    public function executeCreate($descr = '') {
        $versionName = $this->manager->createVersionFile($descr);
        if ($versionName) {
            Out::out('%s created', $versionName);
        } else {
            Out::out('error');
        }
    }

    public function executeList() {
        $versions = $this->manager->getVersions();

        $titles = array(
            'is_new' => '(new)',
            'is_success' => '',
            'is_404' => '(unknown)',
        );

        foreach ($versions as $item) {
            Out::out('%s %s', $item['version'], $titles[$item['type']]);
        }
    }

    public function executeStatus() {
        $summ = $this->manager->getVersionsSummary();

        $titles = array(
            'is_new' =>     'new migrations',
            'is_success' => 'success',
            'is_404' =>     'unknown',
        );

        foreach ($summ as $type => $cnt) {
            Out::out('%s: %d', $titles[$type], $cnt);
        }

    }

    public function executeMigrate($up = '--up') {
        if ($up == '--up') {
            $success = $this->doExecuteAll('up');
            Out::out('migrations up: %d', $success);

        } elseif ($up == '--down') {
            $success = $this->doExecuteAll('down');
            Out::out('migrations down: %d', $success);

        } else {
            $this->seeHelp();
        }
    }

    public function executeUp($limit = 1) {
        $limit = (int)$limit;
        if ($limit > 0) {
            $success = $this->doExecuteAll('up', $limit);
            Out::out('migrations up: %d', $success);
        } else {
            $this->seeHelp();
        }
    }

    public function executeDown($limit = 1) {
        $limit = (int)$limit;
        if ($limit > 0) {
            $success = $this->doExecuteAll('down', $limit);
            Out::out('migrations down: %d', $success);
        } else {
            $this->seeHelp();
        }
    }

    public function executeExecute($version, $up = '--up') {
        if ($version && $up == '--up') {

            $ok = $this->doExecuteOnce($version, 'up');
            Out::out($ok ? '%s up success' : '%s up error', $version);

        } elseif ($version && $up == '--down') {

            $ok = $this->doExecuteOnce($version, 'down');
            Out::out($ok ? '%s down success' : '%s down error', $version);


        } else {
            $this->seeHelp();
        }
    }

    public function executeRedo($version) {
        if ($version) {
            $ok1 = $this->doExecuteOnce($version, 'down');
            $ok2 = $this->doExecuteOnce($version, 'up');

            $ok1 = $ok1 ? 'success' : 'error';
            $ok2 = $ok2 ? 'success' : 'error';

            Out::out('%s down: %s, up: %s', $version, $ok1, $ok2);

        } else {
            $this->seeHelp();
        }
    }

    public function executeHelp() {
        $cmd = Utils::getModuleDir() . '/tools/commands.txt';
        if (is_file($cmd)){
            Out::out(file_get_contents($cmd));
        }
    }
    
    protected function seeHelp(){
        Out::out('Required params not found, see help');
    }


    protected function doExecuteAll($action = 'up', $limit = 0) {
        $action = ($action == 'up') ? 'up' : 'down';
        $limit = (int)$limit;

        $success = 0;

        $items = $this->manager->getVersionsFor($action);
        foreach ($items as $version) {
            if ($this->doExecuteOnce($version, $action)) {
                $success++;
            }

            if ($limit > 0 && $limit == $success) {
                break;
            }
        }

        return $success;
    }

    protected function doExecuteOnce($version, $action = 'up') {
        $action = ($action == 'up') ? 'up' : 'down';
        $params = array();
        do {
            $restart = 0;
            $ok = $this->manager->executeVersion($version, $action, $params);
            if ($this->manager->needRestart($version)) {
                $params = $this->manager->getRestartParams($version);
                $restart = 1;
            }

        } while ($restart == 1);

        return $ok;
    }

    protected function camelizeText($str, $prefix = '') {
        $str = str_replace(array('_', '-', ' '), '*', $str);
        $str = explode('*', $str);

        $tmp = !empty($prefix) ? array($prefix) : array();
        foreach ($str as $val) {
            $tmp[] = ucfirst(strtolower($val));
        }

        return implode('', $tmp);
    }

}
