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
            if (empty($field['bind'])) {
                fwrite(STDOUT, $field['title'] . ':');
                $val = fgets(STDIN);
                $postvars[$code] = trim($val);
            }
        }

        $builder->bind($postvars);

        $versionName = $builder->build();

        $meta = $versionManager->getVersionByName($versionName);

        if (!empty($meta['class'])) {
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
            Out::out('Invalid arguments, see help');
            die(1);
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

        $table = new ConsoleTable(-1, array(
            'horizontal' => '=',
            'vertical' => '',
            'intersection' => ''
        ), 1, 'UTF-8');

        $table->setHeaders(array(
            'Version',
            'Status',
            'Description',
        ));

        foreach ($versions as $index => $aItem) {
            $table->addRow(array(
                $aItem['version'],
                GetMessage('SPRINT_MIGRATION_META_' . strtoupper($aItem['status'])),
                $aItem['description'],
            ));

            $stval = $aItem['status'];
            $summary[$stval]++;
        }

        Out::out($table->getTable());

        $table = new ConsoleTable(-1, '', 1, 'UTF-8');
        foreach ($summary as $k => $v) {
            $table->addRow(array(
                GetMessage('SPRINT_MIGRATION_META_' . strtoupper($k)) . ':',
                $v
            ));
        }

        Out::out($table->getTable());

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
            die(1);
        }
    }

    public function commandRedo(){
        $version = $this->getArg(0, '');
        $force = $this->getArg('--force');
        if ($version) {
            $this->executeVersion($version, 'down', $force);
            $this->executeVersion($version, 'up', $force);
        } else {
            Out::out('Version not found!');
            die(1);
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

            $table = new ConsoleTable(-1, array(
                'horizontal' => '=',
                'vertical' => '',
                'intersection' => ''
            ), 1, 'UTF-8');

            $table->setBorderVisibility(array('bottom' => false));

            foreach ($configItem['values'] as $key => $val) {

                if ($key == 'version_builders') {
                    $val = array_keys($val);
                    $val = implode(PHP_EOL, $val);
                } elseif ($key == 'show_other_solutions') {
                    $val = ($val) ? 'yes' : 'no';
                    $val = GetMessage('SPRINT_MIGRATION_CONFIG_' . $val);
                } elseif ($key == 'stop_on_errors') {
                    $val = ($val) ? 'yes' : 'no';
                    $val = GetMessage('SPRINT_MIGRATION_CONFIG_' . $val);
                }

                $table->addRow(array($key, $val));
            }

            Out::out($table->getTable());
        }
    }

    protected function outVersionMeta($meta = array()) {
        $table = new ConsoleTable(-1, array(
            'horizontal' => '=',
            'vertical' => '',
            'intersection' => ''
        ), 1, 'UTF-8');

        $table->setBorderVisibility(array('bottom' => false));

        foreach (array('version', 'status', 'description', 'location') as $param) {
            if (empty($meta[$param])) {
                continue;
            }

            if ($param == 'status') {
                $val = GetMessage('SPRINT_MIGRATION_META_' . strtoupper($meta[$param]));
            } else {
                $val = $meta[$param];
            }

            $table->addRow(array(ucfirst($param) . ':', $val));
        }

        Out::out($table->getTable());
    }


    protected function executeAll($filter, $limit = 0, $force = false) {
        $versionManager = $this->getVersionManager();
        $limit = (int)$limit;

        $success = 0;
        $fails = 0;

        $versions = $versionManager->getVersions($filter);

        $action = ($filter['status'] == 'new') ? 'up' : 'down';

        foreach ($versions as $aItem) {

            $ok = $this->executeVersion($aItem['version'], $action, $force);

            if ($ok) {
                $success++;
            } else {
                $fails++;
            }

            if ($fails && $versionManager->getConfigVal('stop_on_errors')){
                break;
            }

            if ($limit > 0 && $limit == $success) {
                break;
            }
        }

        Out::out('migrations (%s): %d', $action, $success);

        if ($fails) {
            die(1);
        }
    }

    protected function executeOnce($version, $action = 'up', $force = false) {
        $ok = $this->executeVersion($version, $action, $force);

        if (!$ok){
            die(1);
        }

    }

    protected function executeVersion($version, $action = 'up', $force = false) {
        $versionManager = $this->getVersionManager();
        $params = array();

        Out::out('%s (%s) start', $version, $action);

        do {
            $exec = 0;

            $success = $versionManager->startMigration($version, $action, $params, $force);
            $restart = $versionManager->needRestart($version);

            if ($restart) {
                $params = $versionManager->getRestartParams($version);
                $exec = 1;
            }

            if ($success && !$restart) {
                Out::out('%s (%s) success', $version, $action);
            }

            if (!$success && !$restart) {
                Out::out('%s (%s) error: %s',
                    $version,
                    $action,
                    $versionManager->getLastException()->getMessage()
                );
            }

        } while ($exec == 1);

        return $success;
    }


    public function executeConsoleCommand($args) {
        $this->script = array_shift($args);

        if (empty($args)) {
            $this->commandHelp();
            die(1);
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
            Out::out('Command not found, see help');
            die(1);
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

    public function commandMi() {
        $this->commandMigrate();
    }

    public function commandAdd() {
        $this->commandCreate();
    }
}
