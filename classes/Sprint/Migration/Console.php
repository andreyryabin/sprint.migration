<?php

namespace Sprint\Migration;

class Console
{

    private $vmInstance = null;

    private $script = 'migrate.php';

    private $arguments = array();

    public function __construct() {
        //
    }

    protected function versionManager() {
        if (!$this->vmInstance) {
            $configName = $this->getArg('--config', '');
            $this->vmInstance = new VersionManager($configName);
        }
        return $this->vmInstance;
    }

    public function commandCreate() {
        /** @compability */
        $descr = $this->getArg(0, '');
        /** @compability */
        $prefix = $this->getArg(1, '');
        /** @compability */
        $prefix = $this->getArg('--name', $prefix);

        $descr = $this->getArg('--desc', $descr);
        $prefix = $this->getArg('--prefix', $prefix);

        $meta = $this->versionManager()->createVersionFile($descr, $prefix);
        $this->outVersionMeta($meta);
    }

    public function commandList() {
        $search = $this->getArg('--search');
        $status = ($this->getArg('--new')) ? 'new' : '';

        $versions = $this->versionManager()->getVersions(array(
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
            $meta = $this->versionManager()->getVersionMeta($version);
            $this->outVersionMeta($meta);

        } else {
            $search = $this->getArg('--search');
            $versions = $this->versionManager()->getVersions(array(
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

        $search = $this->getArg('--search');
        $status = ($this->getArg('--down')) ? 'installed' : 'new';

        $this->executeAll(array(
            'status' => $status,
            'search' => $search
        ), 0, $force);
    }

    public function commandUp() {
        $force = $this->getArg('--force');
        $var = $this->getArg(0, 1);

        $search = $this->getArg('--search');
        $status = 'new';

        if ($this->versionManager()->checkVersionName($var)) {
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

        $search = $this->getArg('--search');
        $status = 'installed';

        if ($this->versionManager()->checkVersionName($var)) {
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
        Out::out('Директория с миграциями:' . PHP_EOL . '  %s' . PHP_EOL, $this->versionManager()->getConfigVal('migration_dir'));
        Out::out('Запуск:' . PHP_EOL . '  php %s <command> [<args>]' . PHP_EOL, $this->script);
        Out::out(file_get_contents(Module::getModuleDir() . '/commands.txt'));
        Out::out(PHP_EOL . 'Пожелания и ошибки присылайте сюда');
        Out::out('  https://bitbucket.org/andrey_ryabin/sprint.migration/issues/new' . PHP_EOL);
    }

    public function commandConfig() {
        $configInfo = $this->versionManager()->getConfigInfo();
        $configName = $this->versionManager()->getConfigName();

        foreach ($configInfo as $file) {
            $current = ($file['name'] == $configName) ? '*' : '';
            Out::out('%s %s', $file['title'], $current);
            Out::initTable(array());
            foreach ($file['values'] as $key => $val) {
                Out::addTableRow(array($key, $val));
            }
            Out::outTable();
            Out::out('');
        }
    }

    protected function executeAll($filter, $limit = 0, $force = false) {
        $limit = (int)$limit;

        $success = 0;

        $versions = $this->versionManager()->getVersions($filter);

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

            $ok = $this->versionManager()->startMigration($version, $action, $params, $force);
            if ($this->versionManager()->needRestart($version)) {
                $params = $this->versionManager()->getRestartParams($version);
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
            Out::out('Version not found!');
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
            $val = is_null($val) ? 1 : $val;
            $this->arguments[$name] = $val;
        } else {
            $this->arguments[] = $name;
        }
    }

    protected function getArg($name, $default = '') {
        return isset($this->arguments[$name]) ? $this->arguments[$name] : $default;
    }
}
