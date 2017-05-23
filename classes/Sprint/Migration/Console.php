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
    protected function getVersionManager() {
        if (!$this->versionManager) {
            $this->versionManager = new VersionManager($this->getArg('--config='));
        }
        return $this->versionManager;
    }

    public function commandCreate() {
        $versionManager = $this->getVersionManager();
        /** @compability */
        $descr = $this->getArg(0, '');
        /** @compability */
        $prefix = $this->getArg(1, '');
        /** @compability */
        $prefix = $this->getArg('--name=', $prefix);

        $descr = $this->getArg('--desc=', $descr);
        $prefix = $this->getArg('--prefix=', $prefix);
        $from = $this->getArg('--from=', '');

        $builder = $versionManager->createVersionBuilder($from);

        $builder->bind(array(
            'description' => $descr,
            'prefix' => $prefix,
        ));

        $fields = $builder->getFields();

        $postvars = array();
        foreach ($fields as $code => $field) {
            if (empty($field['bind'])){
                fwrite(STDOUT, $field['title'] . ':');
                $val = fgets(STDIN);
                $postvars[$code] = trim($val);
            }
        }

        $builder->bind($postvars);

        $versionName = $builder->build();

        $meta = $versionManager->getVersionByName($versionName);
        if ($meta){
            Out::out(GetMessage('SPRINT_MIGRATION_CREATED_SUCCESS2'));
            $this->outVersionMeta($meta);
        }
    }

    public function commandMark() {
        $versionManager = $this->getVersionManager();

        $search = $this->getArg(0, '');
        $status = $this->getArg('--as=', '');

        if ($search && $status) {
            $markresult = $versionManager->markMigration($search, $status);
            foreach ($markresult as $val) {
                Out::out($val['message']);
            }
        } else {
            Throw new \Exception('Command not found, see help');
        }
    }

    public function commandList() {
        $versionManager = $this->getVersionManager();
        $search = $this->getArg('--search=');

        if ($this->getArg('--new')) {
            $status = 'new';
        } elseif ($this->getArg('--installed')) {
            $status = 'installed';
        } else {
            $status = '';
        }

        $versions = $versionManager->getVersions(array(
            'status' => $status,
            'search' => $search
        ));

        if ($status) {
            $summary = array();
            $summary[$status] = 0;
        } else {
            $summary = array(
                'new' => 0,
                'installed' => 0,
                'unknown' => 0
            );
        }

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

            $stval = $aItem['status'];
            $summary[$stval]++;
        }
        Out::outTable();

        Out::initTable(array(), false);
        foreach ($summary as $k => $v) {
            Out::addTableRow(array(
                GetMessage('SPRINT_MIGRATION_META_' . strtoupper($k)) . ':',
                $v
            ));
        }

        Out::outTable();

    }

    public function commandStatus() {
        $versionManager = $this->getVersionManager();
        $version = $this->getArg(0, '');
        $meta = $versionManager->getVersionByName($version);

        if ($meta){
            $this->outVersionMeta($meta);
        } else {
            Throw new \Exception('Version not found!');
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
        $versionManager = $this->getVersionManager();
        $force = $this->getArg('--force');
        $var = $this->getArg(0, 1);

        $search = $this->getArg('--search=');
        $status = 'new';

        if ($versionManager->checkVersionName($var)) {
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
        $versionManager = $this->getVersionManager();
        $force = $this->getArg('--force');
        $var = $this->getArg(0, 1);

        $search = $this->getArg('--search=');
        $status = 'installed';

        if ($versionManager->checkVersionName($var)) {
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
            Throw new \Exception('Version not found!');
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
            Throw new \Exception('Version not found!');
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

        Out::out('Запуск:' . PHP_EOL . '  php %s <command> [<args>]' . PHP_EOL, $this->script);
        Out::out(file_get_contents(Module::getModuleDir() . '/commands.txt'));
        Out::out(PHP_EOL . 'Пожелания и ошибки присылайте сюда');
        Out::out('  https://bitbucket.org/andrey_ryabin/sprint.migration/issues/new' . PHP_EOL);
    }

    public function commandConfig() {
        $versionManager = $this->getVersionManager();
        $configList = $versionManager->getConfigList();
        $configName = $versionManager->getConfigName();

        foreach ($configList as $configItem) {
            $current = ($configItem['name'] == $configName) ? '*' : '';
            Out::out('%s %s', $configItem['title'], $current);
            Out::initTable(array(),false);
            foreach ($configItem['values'] as $key => $val) {
                $val = is_array($val) ? implode(',', $val) : $val;
                Out::addTableRow(array($key, $val));
            }
            Out::outTable();
        }
    }

    protected function executeAll($filter, $limit = 0, $force = false) {
        $versionManager = $this->getVersionManager();
        $limit = (int)$limit;

        $success = 0;

        $versions = $versionManager->getVersions($filter);

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
        $versionManager = $this->getVersionManager();
        $params = array();
        $restart = 0;

        do {
            $exec = 0;

            if (!$restart) {
                Out::out('%s (%s) start', $version, $action);
            }

            $success = $versionManager->startMigration($version, $action, $params, $force);
            $restart = $versionManager->needRestart($version);

            if ($restart) {
                $params = $versionManager->getRestartParams($version);
                $exec = 1;
            }

            if ($success && !$restart){
                Out::out('%s (%s) success', $version, $action);
            }

            if (!$success && !$restart) {
                $error = sprintf('%s (%s) error: %s',$version, $action, $versionManager->getLastError());
                if ($versionManager->getConfigVal('stop_on_errors') == 'yes'){
                    Throw new \Exception($error);
                } else {
                    Out::outError($error);
                }
            }

        } while ($exec == 1);

        return $success;
    }

    protected function outVersionMeta($meta) {
        Out::initTable(array(), false);
        foreach (array('version', 'status', 'description', 'location') as $val) {
            if (!empty($meta[$val])) {
                if ($val == 'status') {
                    Out::addTableRow(array(
                        ucfirst($val) . ':',
                        GetMessage('SPRINT_MIGRATION_META_' . strtoupper($meta[$val]))
                    ));
                } else {
                    Out::addTableRow(array(ucfirst($val) . ':', $meta[$val]));
                }
            }
        }
        Out::outTable();
    }

    //
    public function executeConsoleCommand($args) {
        $this->script = array_shift($args);

        if (empty($args)) {
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

        if (method_exists($this, $command)) {
            $this->initializeArgs($args);
            call_user_func(array($this, $command));
        } else {
            Throw new \Exception('Command not found, see help');
        }
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
            if (!is_null($val)) {
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

    public function commandLs() {
        $this->commandList();
    }

    public function commandSt() {
        $this->commandStatus();
    }

    public function commandAdd() {
        $this->commandCreate();
    }
}
