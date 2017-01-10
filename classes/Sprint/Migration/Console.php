<?php

namespace Sprint\Migration;

class Console
{

    private $script = 'migrate.php';
    private $arguments = array();
    private $versionManager = null;

    public function __construct() {
        //
    }

    /** @return VersionManager */
    protected function getVersionManager(){
        if (!$this->versionManager){
            $this->versionManager = new VersionManager($this->getArg('--config='));
        }
        return $this->versionManager;
    }

    public function commandCreate() {
        /** @compability */
        $descr = $this->getArg(0, '');
        /** @compability */
        $prefix = $this->getArg(1, '');
        /** @compability */
        $prefix = $this->getArg('--name=', $prefix);

        $descr = $this->getArg('--desc=', $descr);
        $prefix = $this->getArg('--prefix=', $prefix);

        $this->outVersionMeta(
            $this->getVersionManager()->createVersionFile($descr, $prefix)
        );
    }

    public function commandMark() {
        $search = $this->getArg(0,'');
        $status = $this->getArg('--as=', '');

        if ($search && $status){
            $markresult = $this->getVersionManager()->markMigration($search, $status);
            foreach ($markresult as $val){
                Out::out($val['message']);
            }
        } else {
            Out::out('Command error, see help');
        }
    }

    public function commandList() {
        $search = $this->getArg('--search=');

        if ($this->getArg('--new')){
            $status = 'new';
        } elseif ($this->getArg('--installed')){
            $status = 'installed';
        } else {
            $status = '';
        }

        $versions = $this->getVersionManager()->getVersions(array(
            'status' => $status,
            'search' => $search
        ));

        Out::initTable(array(
            'Version',
            'Status',
            'Description',
            //'Location'
        ));
        foreach ($versions as $aItem) {
            Out::addTableRow(array(
                $aItem['version'],
                GetMessage('SPRINT_MIGRATION_META_' . strtoupper($aItem['status'])),
                $aItem['description'],
                //$aItem['location'],
            ));
        }

        Out::outTable();
    }

    public function commandStatus() {
        $version = $this->getArg(0, '');

        if ($version) {
            $this->outVersionMeta(
                $this->getVersionManager()->getVersionByName($version)
            );

        } else {
            $search = $this->getArg('--search=');
            $versions = $this->getVersionManager()->getVersions(array(
                'status' => '',
                'search' => $search
            ));

            $status = array(
                'new' => 0,
                'installed' => 0,
                'unknown' => 0,
            );

            foreach ($versions as $aItem) {
                $key = $aItem['status'];
                $status[$key]++;
            }

            Out::initTable(array('Status', 'Count'));
            foreach ($status as $k => $v) {
                Out::addTableRow(array(GetMessage('SPRINT_MIGRATION_META_' . strtoupper($k)), $v));
            }
            Out::outTable();
        }
    }

    public function commandMigrate() {
        $force = $this->getArg('--force');

        $search = $this->getArg('--search=');
        $status = ($this->getArg('--down')) ? 'installed' : 'new';

        $this->executeAll(array(
            'status' => $status,
            'search' => $search
        ), 0, $force);
    }

    public function commandUp() {
        $force = $this->getArg('--force');
        $var = $this->getArg(0, 1);

        $search = $this->getArg('--search=');
        $status = 'new';

        if ($this->getVersionManager()->checkVersionName($var)) {
            $this->executeOnce($var, 'up', $force);

        } elseif ($this->getArg('--all')) {
            $this->executeAll(array(
                'status' => $status,
                'search' => $search
            ), 0, $force);

        } else {
            $this->executeAll(array(
                'status' => $status,
                'search' => $search
            ), intval($var), $force);
        }

    }

    public function commandDown() {
        $force = $this->getArg('--force');
        $var = $this->getArg(0, 1);

        $search = $this->getArg('--search=');
        $status = 'installed';

        if ($this->getVersionManager()->checkVersionName($var)) {
            $this->executeOnce($var, 'down', $force);

        } elseif ($this->getArg('--all')) {
            $this->executeAll(array(
                'status' => $status,
                'search' => $search
            ), 0, $force);

        } else {
            $this->executeAll(array(
                'status' => $status,
                'search' => $search
            ), intval($var), $force);
        }

    }

    public function commandRedo() {
        $version = $this->getArg(0, '');
        $force = $this->getArg('--force');
        if ($version) {
            $this->executeOnce($version, 'down', $force);
            $this->executeOnce($version, 'up', $force);
        } else {
            Out::out('Version not found!');
        }
    }

    public function commandExecute() {
        $version = $this->getArg(0, '');
        $force = $this->getArg('--force');
        if ($version) {
            if ($this->getArg('--down')) {
                $this->executeOnce($version, 'down', $force);
            } else {
                $this->executeOnce($version, 'up', $force);
            }
        } else {
            Out::out('Version not found!');
        }
    }

    public function commandForce() {
        /** @compability */
        $this->addArg('--force');
        $this->commandExecute();
    }

    public function commandHelp() {
        Out::out(GetMessage('SPRINT_MIGRATION_MODULE_NAME'));
        Out::out('Версия bitrix: %s', defined('SM_VERSION') ? SM_VERSION : '');
        Out::out('Версия модуля: %s', Module::getVersion());
        Out::out('');

        $configItem = $this->getVersionManager()->getConfigCurrent();
        Out::out($configItem['title']);
        Out::initTable(array());
        foreach ($configItem['values'] as $key => $val) {
            Out::addTableRow(array($key, $val));
        }
        Out::outTable();

        Out::out('');
        Out::out('Запуск:' . PHP_EOL . '  php %s <command> [<args>]' . PHP_EOL, $this->script);
        Out::out(file_get_contents(Module::getModuleDir() . '/commands.txt'));
        Out::out(PHP_EOL . 'Пожелания и ошибки присылайте сюда');
        Out::out('  https://bitbucket.org/andrey_ryabin/sprint.migration/issues/new' . PHP_EOL);
    }

    public function commandConfig() {
        $configList = $this->getVersionManager()->getConfigList();
        $configName = $this->getVersionManager()->getConfigName();

        foreach ($configList as $configItem) {
            $current = ($configItem['name'] == $configName) ? '*' : '';
            Out::out('%s %s', $configItem['title'], $current);
            Out::initTable(array());
            foreach ($configItem['values'] as $key => $val) {
                Out::addTableRow(array($key, $val));
            }
            Out::outTable();
            Out::out('');
        }
    }

    protected function executeAll($filter, $limit = 0, $force = false) {
        $limit = (int)$limit;

        $success = 0;

        $versions = $this->getVersionManager()->getVersions($filter);

        $action = ($filter['status'] == 'new') ? 'up' : 'down';

        foreach ($versions as $aItem) {
            if ($this->executeOnce($aItem['version'], $action, $force)) {
                $success++;
            }

            if ($limit > 0 && $limit == $success) {
                break;
            }
        }

        Out::out('migrations (%s): %d', $action, $success);
        return $success;
    }

    protected function executeOnce($version, $action = 'up', $force = false) {
        $params = array();
        $restart = 0;

        do {
            $exec = 0;

            if (!$restart) {
                Out::out('%s (%s) start', $version, $action);
            }

            $ok = $this->getVersionManager()->startMigration($version, $action, $params, $force);
            if ($this->getVersionManager()->needRestart($version)) {
                $params = $this->getVersionManager()->getRestartParams($version);
                $restart = 1;
                $exec = 1;
            }

        } while ($exec == 1);

        return $ok;
    }

    protected function outVersionMeta($meta = false) {
        if ($meta) {
            Out::initTable();
            foreach (array('version', 'status', 'description', 'location') as $val) {
                if (!empty($meta[$val])) {
                    if ($val == 'status') {
                        Out::addTableRow(array(ucfirst($val), GetMessage('SPRINT_MIGRATION_META_' . strtoupper($meta[$val]))));
                    } else {
                        Out::addTableRow(array(ucfirst($val), $meta[$val]));
                    }
                }
            }
            Out::outTable();
        } else {
            Out::out(GetMessage('SPRINT_MIGRATION_VERSION_NOT_FOUND'));
        }
    }

    //
    public function executeConsoleCommand($args) {
        $this->script = array_shift($args);

        if (empty($args) || count($args) <= 0) {
            $this->commandHelp();
            return false;
        }

        $command = array_shift($args);

        $command = str_replace(array('_', '-', ' '), '*', $command);
        $command = explode('*', $command);
        $tmp = array();
        foreach ($command as $val) {
            $tmp[] = ucfirst(strtolower($val));
        }

        $command = 'command' . implode('', $tmp);

        if (!method_exists($this, $command)) {
            Out::out('Command not found, see help');
            return false;
        }

        $this->initializeArgs($args);

        call_user_func(array($this, $command));
        return true;
    }

    protected function initializeArgs($args) {
        foreach ($args as $val) {
            $this->addArg($val);
        }
    }

    protected function addArg($arg) {
        list($name, $val) = explode('=', $arg);
        $isparam = (0 === strpos($name, '--')) ? 1 : 0;
        if ($isparam) {
            if (!is_null($val)){
                $this->arguments[$name . '='] = $val;
            } else {
                $this->arguments[$name] = 1;
            }
        } else {
            $this->arguments[] = $name;
        }
    }

    protected function getArg($name, $default = '') {
        return isset($this->arguments[$name]) ? $this->arguments[$name] : $default;
    }
}
