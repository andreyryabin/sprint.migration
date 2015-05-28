<?php

namespace Sprint\Migration;

class Console
{

    protected $help = array();
    protected $manager = null;

    public function __construct() {
        $this->manager = new Manager();
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

    public function executeCreate($descr = '') {
        $versionName = $this->manager->createVersionFile($descr);
        if ($versionName) {
            Out::outSuccess('%s created', $versionName);
        } else {
            Out::outError('error');
        }
    }


    public function executeStatus() {
        $versions = $this->manager->getVersions();

        foreach ($versions as $item) {
            $name = $item['version'];
            Out::out('[%s]%s[/]', $item['type'], $name);
        }

        $info = array(
            'is_new' => array('title' => 'New migrations', 'cnt' => 0),
            'is_success' => array('title' => 'Success', 'cnt' => 0),
            'is_404' => array('title' => 'Unknown', 'cnt' => 0),
        );

        foreach ($versions as $item) {
            $type = $item['type'];
            $info[$type]['cnt']++;
        }

        foreach ($info as $type => $aItem) {
            Out::out('[%s]%s[/]: %d', $type, $aItem['title'], $aItem['cnt']);
        }

    }

    public function executeMigrate($up = '--up') {
        if ($up == '--up') {
            $success = $this->doExecuteAll('up');
            Out::out('Migrations up: [green]%d[/]', $success);

        } elseif ($up == '--down') {
            $success = $this->doExecuteAll('down');
            Out::out('Migrations down: [red]%d[/]', $success);

        } else {
            Out::out('[red]required params not found[/]');
        }
    }

    public function executeUp($limit = 1) {
        $limit = (int)$limit;
        if ($limit > 0) {
            $success = $this->doExecuteAll('up', $limit);
            Out::out('Migrations up: [green]%d[/]', $success);
        } else {
            Out::out('[red]required COUNT not found[/]');
        }
    }

    public function executeDown($limit = 1) {
        $limit = (int)$limit;
        if ($limit > 0) {
            $success = $this->doExecuteAll('down', $limit);
            Out::out('Migrations down: [green]%d[/]', $success);
        } else {
            Out::out('[red]required COUNT not found[/]');
        }
    }

    public function executeExecute($version, $up = '--up') {
        if ($version && $up == '--up') {

            $ok = $this->doExecuteOnce($version, 'up');
            Out::out($ok ? '[green]%s success[/]' : '[red]%s error[/]', $version);

        } elseif ($version && $up == '--down') {

            $ok = $this->doExecuteOnce($version, 'down');
            Out::out($ok ? '[green]%s success[/]' : '[red]%s error[/]', $version);


        } else {
            Out::out('[red]required params not found[/]');
        }
    }

    public function executeRedo($version) {
        if ($version) {
            $ok1 = $this->doExecuteOnce($version, 'down');
            $ok2 = $this->doExecuteOnce($version, 'up');

            $ok1 = $ok1 ? '[green]success[/]' : '[red]error[/]';
            $ok2 = $ok2 ? '[green]success[/]' : '[red]error[/]';

            Out::out('%s %s+%s', $version, $ok1, $ok2);

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

    protected function doExecuteOnce($version, $action = 'up', $params = array()) {
        $action = ($action == 'up') ? 'up' : 'down';
        $ok = $this->manager->executeVersion($version, $action, $params);
        if ($this->manager->needRestart($version)) {
            $params = $this->manager->getRestartParams($version);
            $ok = $this->doExecuteOnce($version, $action, $params);
        }
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

    protected function initHelp() {
        $this->help['Create'] = '<description> add new migration with description';
        $this->help['Status'] = 'get migrations list';
        $this->help['Migrate'] = '[b]--up[/] --down up or down all migrations';
        $this->help['Up'] = '<limit> up limit migrations';
        $this->help['Down'] = '<limit> down migrations';

        $this->help['Execute'] = '<version> [b]--up[/] --down up or down this migration';
        $this->help['Redo'] = '<version> down+up this migration';
    }

}
