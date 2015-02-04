<?php

namespace Sprint\Migration;

class Console
{

    protected $colors = array();
    protected $help = array();
    protected $manager = null;


    public function __construct() {
        $this->initColors();
        $this->initHelp();
    }

    public function execFromArgs($args) {

        if (empty($args) || count($args) <= 1) {
            $this->out('available commands:');
            $this->executeHelp();
            return false;
        }

        $first = array_shift($args);
        $method = array_shift($args);
        $method = 'execute' . $this->camelizeText($method);

        if (!method_exists($this, $method)) {
            $this->out('command %s not found', $method);
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

    protected function executeCreate($descr = '') {
        $ok = $this->getMigrationManager()->createVersionFile($descr);
        $this->out($ok ? '[green]success[/]' : '[red]error[/]');

    }


    protected function executeStatus($mode = '--info') {
        $versions = $this->getMigrationManager()->getVersions();

        $mode = ($mode && in_array($mode, array('--new', '--all','--info'))) ? $mode : '--new';

        if ($mode == '--all'){
            $cnt = 0;
            foreach ($versions as $item) {
                $color = str_replace(array('is_new', 'is_success', 'is_404'), array('red', 'green', 'blue'), $item['type']);
                $name = $item['version'];
                $this->out('[%s]%s[/]', $color, $name);
                $cnt++;
            }
            $this->out('Found %d migrations', $cnt);
        }

        if ($mode == '--new'){
            $cnt = 0;
            foreach ($versions as $item) {
                if ($item['type'] != 'is_new'){
                    continue;
                }

                $color = str_replace(array('is_new', 'is_success', 'is_404'), array('red', 'green', 'blue'), $item['type']);
                $name = $item['version'];
                $this->out('[%s]%s[/]', $color, $name);
                $cnt++;
            }
            $this->out('Found %d migrations', $cnt);
        }

        if ($mode == '--info'){
            $info = array(
                'is_new' => array('title' => 'New migrations','cnt' => 0,'color' => 'red'),
                'is_success' => array('title' => 'Success','cnt' => 0,'color' => 'green'),
                'is_404' => array('title' => 'Unknown','cnt' => 0,'color' => 'blue'),
            );

            foreach ($versions as $item) {
                $type = $item['type'];
                $info[$type]['cnt']++;
            }

            foreach ($info as $type=>$aItem){
                $this->out('[%s]%s[/]: %d', $aItem['color'], $aItem['title'], $aItem['cnt']);
            }
        }

    }

    protected function executeMigrate($up = '--up') {
        if ($up == '--up') {
            $cnt = $this->getMigrationManager()->executeMigrateUp();
            $this->out('Migrations up: [green]%d[/]', $cnt);

        } elseif ($up == '--down') {
            $cnt = $this->getMigrationManager()->executeMigrateDown();
            $this->out('Migrations down: [red]%d[/]', $cnt);

        } else {
            $this->out('[red]required params not found[/]');
        }
    }

    protected function executeUp($cnt = 1) {
        $cnt = (int)$cnt;
        if ($cnt > 0) {
            $cntSuccess = $this->getMigrationManager()->executeMigrateUp($cnt);
            $this->out('Migrations up: [green]%d[/]', $cntSuccess);
        } else {
            $this->out('[red]required COUNT not found[/]');
        }
    }

    protected function executeDown($cnt = 1) {
        $cnt = (int)$cnt;
        if ($cnt > 0) {
            $cntSuccess = $this->getMigrationManager()->executeMigrateDown($cnt);
            $this->out('Migrations up: [green]%d[/]', $cntSuccess);
        } else {
            $this->out('[red]required COUNT not found[/]');
        }

    }

    protected function executeExecute($version, $up = '--up') {
        if ($version && $up == '--up') {
            $ok = $this->getMigrationManager()->executeVersion($version, true);
            $this->out($ok ? '[green]success[/]' : '[red]error[/]');
        } elseif ($version && $up == '--down') {
            $ok = $this->getMigrationManager()->executeVersion($version, false);
            $this->out($ok ? '[green]success[/]' : '[red]error[/]');
        } else {
            $this->out('[red]required params not found[/]');
        }
    }

    protected function executeRedo($version) {
        if ($version) {
            $ok1 = $this->getMigrationManager()->executeVersion($version, false);
            $ok2 = $this->getMigrationManager()->executeVersion($version, true);

            $ok1 = $ok1 ? '[green]success[/]' : '[red]error[/]';
            $ok2 = $ok2 ? '[green]success[/]' : '[red]error[/]';

            $this->out('%s+%s', $ok1, $ok2);

        } else {
            $this->out('[red]required params not found[/]');
        }
    }

    protected function executeHelp() {
        $res = get_class_methods($this);

        foreach ($res as $val) {
            if (false !== strpos($val, 'execute')) {
                $val = ltrim($val, 'execute');

                if (isset($this->help[$val])) {
                    $this->out('[green]%s[/] %s', $val, $this->help[$val]);
                } else {
                    $this->out($val);
                }
            }
        }
    }

    protected function out($msg, $var1 = null, $var2 = null) {
        ob_end_flush();

        if (func_num_args() > 1) {
            $params = func_get_args();
            $msg = call_user_func_array('sprintf', $params);
        }

        foreach ($this->colors as $key => $val) {
            $msg = str_replace('[' . $key . ']', "\033[" . $val . "m", $msg);
        }

        $msg = str_replace('[/]', "\033[0m", $msg);

        echo $msg . "\n";

        @ob_flush();
    }

    protected function initColors() {
        $this->colors['blue'] = '0;34';
        $this->colors['green'] = '0;32';
        $this->colors['red'] = '0;31';
        $this->colors['yellow'] = '1;33';
    }

    protected function initHelp() {
        $this->help['Create'] = '[yellow]<description>[/] add new migration with description';
        $this->help['Status'] = 'get migrations list';
        $this->help['Migrate'] = '[yellow]--up --down[/] - up or down all migrations';
        $this->help['Execute'] = '[yellow]<version>[/] [yellow]--up --down[/] up or down this migration';
        $this->help['Redo'] = '[yellow]<version>[/] down+up this migration';
        $this->help['Up'] = '[yellow]<count>[/] up <count> migrations';
        $this->help['Down'] = '[yellow]<count>[/] down <count> migrations';
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



