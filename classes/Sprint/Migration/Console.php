<?php

namespace Sprint\Migration;

class Console
{

    
    protected $help = array();
    protected $manager = null;

    public function __construct() {
        $this->initHelp();
    }

    public function execFromArgs($args) {

        if (empty($args) || count($args) <= 1) {
            Out::out('available commands:');
            $this->executeHelp();
            return false;
        }

        $first = array_shift($args);
        $method = array_shift($args);
        $method = 'execute' . $this->camelizeText($method);

        if (!method_exists($this, $method)) {
            Out::outError('command %s not found', $method);
            return false;
        }

        call_user_func_array(array($this, $method), $args);
    }

    /* @return Manager */
    protected function getMigrationManager() {
        if (is_null($this->manager)) {
            $this->manager = new Manager();
        }
        return $this->manager;
    }

    public function executeCreate($descr = '') {
        $versionName = $this->getMigrationManager()->createVersionFile($descr);
        if ($versionName){
            Out::outSuccess('%s created', $versionName);
        } else {
            Out::outError('error');
        }


    }


    public function executeStatus($mode = '--info') {
        $versions = $this->getMigrationManager()->getVersions();

        $mode = ($mode && in_array($mode, array('--new', '--all','--info'))) ? $mode : '--new';

        if ($mode == '--all'){
            $cnt = 0;
            foreach ($versions as $item) {
                $name = $item['version'];
                Out::out('[%s]%s[/]', $item['type'], $name);
                $cnt++;
            }
            Out::out('Found %d migrations', $cnt);
        }

        if ($mode == '--new'){
            $cnt = 0;
            foreach ($versions as $item) {
                if ($item['type'] != 'is_new'){
                    continue;
                }

                $name = $item['version'];
                Out::out('[%s]%s[/]', $item['type'], $name);
                $cnt++;
            }
            Out::out('Found %d migrations', $cnt);
        }

        if ($mode == '--info'){
            $info = array(
                'is_new' => array('title' => 'New migrations','cnt' => 0),
                'is_success' => array('title' => 'Success','cnt' => 0),
                'is_404' => array('title' => 'Unknown','cnt' => 0),
            );

            foreach ($versions as $item) {
                $type = $item['type'];
                $info[$type]['cnt']++;
            }

            foreach ($info as $type=>$aItem){
                Out::out('[%s]%s[/]: %d', $type, $aItem['title'], $aItem['cnt']);
            }
        }

    }

    public function executeMigrate($up = '--up') {
        if ($up == '--up') {
            $cnt = $this->getMigrationManager()->executeMigrateUp();
            Out::out('Migrations up: [green]%d[/]', $cnt);

        } elseif ($up == '--down') {
            $cnt = $this->getMigrationManager()->executeMigrateDown();
            Out::out('Migrations down: [red]%d[/]', $cnt);

        } else {
            Out::out('[red]required params not found[/]');
        }
    }

     public function executeExecute($version, $up = '--up', $params = array()) {
        if ($version && $up == '--up') {
            $ok = $this->getMigrationManager()->executeVersion($version, 'up', $params);
            
            if ($this->getMigrationManager()->needRestart($version)){
                $params = $this->getMigrationManager()->getRestartParams($version);
                $this->executeExecute($version, $up, $params);
            } else {
                Out::out($ok ? '[green]success[/]' : '[red]error[/]');    
            }
        } elseif ($version && $up == '--down') {
            $ok = $this->getMigrationManager()->executeVersion($version, 'down');
            if ($this->getMigrationManager()->needRestart($version)){
                $params = $this->getMigrationManager()->getRestartParams($version);
                $this->executeExecute($version, $up, $params);
            } else {
                Out::out($ok ? '[green]success[/]' : '[red]error[/]');
            }            
            
        } else {
            Out::out('[red]required params not found[/]');
        }
    }


    public function executeHelp() {
        $res = get_class_methods($this);

        foreach ($res as $val) {
            if (false !== strpos($val, 'execute')) {
                $val = ltrim($val, 'execute');

                if (isset($this->help[$val])) {
                    Out::out('[green]%s[/] %s', $val, $this->help[$val]);
                } else {
                    Out::out($val);
                }
            }
        }
    }

    protected function initHelp() {
        $this->help['Create'] = '[yellow]<description>[/] add new migration with description';
        $this->help['Status'] = 'get migrations list';
        $this->help['Migrate'] = '[yellow]--up --down[/] - up or down all migrations';
        $this->help['Execute'] = '[yellow]<version>[/] [yellow]--up --down[/] up or down this migration';
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



