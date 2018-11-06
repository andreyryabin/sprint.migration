<?php

namespace Sprint\Migration;

class Console
{

    private $script = 'migrate.php';

    private $arguments = array();
    private $argoptions = array();

    public function executeConsoleCommand($args) {
        $this->script = array_shift($args);

        $command = $this->initializeArgs($args);

        if (empty($command)) {
            $this->commandHelp();
            die(1);
        }


        if (method_exists($this, $command)) {
            call_user_func(array($this, $command));
        } else {
            Out::out('Command "%s" not found, see help', $command);
            die(1);
        }
    }

    public function authorizeAsLogin($login) {
        global $USER;
        $dbres = \CUser::GetByLogin($login);
        $useritem = $dbres->Fetch();
        if ($useritem) {
            $USER->Authorize($useritem['ID']);
        }
    }

    public function authorizeAsAdmin() {
        global $USER;

        $groupitem = \CGroup::GetList($by, $order, array(
            'ADMIN' => 'Y',
            'ACTIVE' => 'Y'
        ))->Fetch();

        if (!empty($groupitem)) {
            $by = 'id';
            $order = 'asc';

            $useritem = \CUser::GetList($by, $order, array(
                'GROUPS_ID' => array($groupitem['ID']),
                'ACTIVE' => 'Y'
            ), array(
                'NAV_PARAMS' => array('nTopCount' => 1)
            ))->Fetch();

            if (!empty($useritem)) {
                $USER->Authorize($useritem['ID']);
            }
        }

    }

    public function commandRun() {
        $this->executeBuilder($this->getArg(0));
    }

    public function commandCreate() {
        /** @compability */
        $descr = $this->getArg(0);
        /** @compability */
        $prefix = $this->getArg(1);
        /** @compability */
        $prefix = $this->getArg('--name=', $prefix);

        $descr = $this->getArg('--desc=', $descr);
        $prefix = $this->getArg('--prefix=', $prefix);
        $from = $this->getArg('--from=', 'Version');

        $this->executeBuilder($from, array(
            'description' => $descr,
            'prefix' => $prefix,
        ));
    }

    public function commandMark() {
        $versionManager = $this->createVersionManager();

        $search = $this->getArg(0);
        $status = $this->getArg('--as=');

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
        $versionManager = $this->createVersionManager();

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

        foreach ($versions as $index => $item) {
            if ($item['modified']) {
                $item['version'] .= ' (' . GetMessage('SPRINT_MIGRATION_MODIFIED_LABEL') . ')';
            }

            $table->addRow(array(
                $item['version'],
                GetMessage('SPRINT_MIGRATION_META_' . strtoupper($item['status'])),
                $item['description'],
            ));

            $stval = $item['status'];
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

    public function commandUp() {
        $versionName = $this->getArg(0);
        $versionManager = $this->createVersionManager();

        if (is_numeric($versionName)) {
            /** @deprecated */
            Out::out('limit is no longer supported');
            die(1);
        }

        if ($versionManager->checkVersionName($versionName)) {
            $this->executeOnce($versionName, 'up', $this->getArg('--force'));
        } else {
            $this->executeAll(array(
                'search' => $this->getArg('--search='),
                'status' => 'new',
            ), $this->getArg('--force'));
        }
    }

    public function commandDown() {
        $versionName = $this->getArg(0);
        $versionManager = $this->createVersionManager();

        if (is_numeric($versionName)) {
            /** @deprecated */
            Out::out('limit is no longer supported');
            die(1);
        }

        if ($versionManager->checkVersionName($versionName)) {
            $this->executeOnce($versionName, 'down', $this->getArg('--force'));
        } else {
            $this->executeAll(array(
                'search' => $this->getArg('--search='),
                'status' => 'installed',
            ), $this->getArg('--force'));
        }
    }

    public function commandRedo() {
        $version = $this->getArg(0);
        $force = $this->getArg('--force');
        if ($version) {
            $this->executeVersion($version, 'down', $force);
            $this->executeVersion($version, 'up', $force);
        } else {
            Out::out('Version not found!');
            die(1);
        }
    }

    public function commandHelp() {
        $versionManager = $this->createVersionManager();
        global $USER;

        Out::out(GetMessage('SPRINT_MIGRATION_MODULE_NAME'));
        Out::out('Версия bitrix: %s', defined('SM_VERSION') ? SM_VERSION : '');
        Out::out('Версия модуля: %s', Module::getVersion());

        if ($USER && $USER->GetID()) {
            Out::out('Текущий пользователь: [%d] %s', $USER->GetID(), $USER->GetLogin());
        }

        $configList = $versionManager->getVersionConfig()->getList();
        $configName = $versionManager->getVersionConfig()->getName();

        Out::out('');

        Out::out('Список конфигураций:');
        foreach ($configList as $configItem) {
            if ($configItem['name'] == $configName) {
                Out::out('  ' . $configItem['title'] . ' *');
            } else {
                Out::out('  ' . $configItem['title']);
            }

        }


        Out::out('');

        Out::out('Запуск:' . PHP_EOL . '  php %s <command> [<args>]' . PHP_EOL, $this->script);
        Out::out(file_get_contents(Module::getModuleDir() . '/commands.txt'));
    }

    public function commandConfig() {
        $versionManager = $this->createVersionManager();

        $configValues = $versionManager->getVersionConfig()->getCurrent('values');
        $configTitle = $versionManager->getVersionConfig()->getCurrent('title');

        Out::out('%s: %s',
            GetMessage('SPRINT_MIGRATION_CONFIG'),
            $configTitle
        );

        $table = new ConsoleTable(-1, array(
            'horizontal' => '=',
            'vertical' => '',
            'intersection' => ''
        ), 1, 'UTF-8');

        $table->setBorderVisibility(array('bottom' => false));

        foreach ($configValues as $key => $val) {
            if ($val === true || $val === false) {
                $val = ($val) ? 'yes' : 'no';
                $val = GetMessage('SPRINT_MIGRATION_CONFIG_' . $val);
            } elseif (is_array($val)) {
                $fres = [];
                foreach ($val as $fkey => $fval) {
                    $fres[] = '[' . $fkey . '] => ' . $fval;
                }
                $val = implode(PHP_EOL, $fres);
            }

            $table->addRow(array($key, $val));
        }

        Out::out($table->getTable());


    }

    public function commandLs() {
        $this->commandList();
    }

    public function commandAdd() {
        $this->commandCreate();
    }

    public function commandMigrate() {
        /** @compability */
        $status = $this->getArg('--down') ? 'installed' : 'new';
        $this->executeAll(array(
            'search' => $this->getArg('--search='),
            'status' => $status,
        ), $this->getArg('--force'));
    }

    public function commandMi() {
        /** @compability */
        $this->commandMigrate();
    }

    public function commandExecute() {
        /** @compability */
        $version = $this->getArg(0);
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

    public function commandForce() {
        /** @compability */
        $this->addArg('--force');
        $this->commandExecute();
    }

    protected function executeAll($filter, $force = false) {
        $versionManager = $this->createVersionManager();

        $success = 0;
        $fails = 0;

        $versions = $versionManager->getVersions($filter);

        $action = ($filter['status'] == 'new') ? 'up' : 'down';

        foreach ($versions as $item) {

            $ok = $this->executeVersion($item['version'], $action, $force);

            if ($ok) {
                $success++;
            } else {
                $fails++;
            }

            if ($fails && $versionManager->getVersionConfig()->getVal('stop_on_errors')) {
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

        if (!$ok) {
            die(1);
        }
    }

    protected function executeVersion($version, $action = 'up', $force = false) {
        $versionManager = $this->createVersionManager();

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

    protected function executeBuilder($from, $postvars = array()) {
        $versionManager = $this->createVersionManager();

        do {

            $builder = $versionManager->createBuilder($from, $postvars);

            if (!$builder) {
                Out::out('Builder not found');
                die(1);
            }

            $builder->renderConsole();

            $builder->executeBuilder();

            $builder->renderConsole();

            $postvars = $builder->getRestartParams();

        } while ($builder->isRestart() || $builder->isRebuild());
    }


    protected function createVersionManager() {
        $versionManager = new VersionManager($this->getArg('--config='));

        $userlogin = $versionManager->getVersionConfig()->getVal('console_user');
        if ($userlogin == 'admin') {
            $this->authorizeAsAdmin();
        } elseif (strpos($userlogin, 'login:') === 0) {
            $userlogin = substr($userlogin, 6);
            $this->authorizeAsLogin($userlogin);
        }

        return $versionManager;
    }

    protected function initializeArgs($args) {
        foreach ($args as $val) {
            $this->addArg($val);
        }

        $command = '';

        if (isset($this->arguments[0])) {
            $command = array_shift($this->arguments);

            $command = str_replace(array('_', '-', ' '), '*', $command);
            $command = explode('*', $command);
            $tmp = array();
            foreach ($command as $val) {
                $tmp[] = ucfirst(strtolower($val));
            }

            $command = 'command' . implode('', $tmp);

        }

        return $command;
    }

    protected function addArg($arg) {
        list($name, $val) = explode('=', $arg);
        $isoption = (0 === strpos($name, '--')) ? 1 : 0;
        if ($isoption) {
            if (!is_null($val)) {
                $this->argoptions[$name . '='] = $val;
            } else {
                $this->argoptions[$name] = 1;
            }
        } else {
            $this->arguments[] = $name;
        }
    }

    protected function getArg($name, $default = '') {
        if (is_numeric($name)) {
            return isset($this->arguments[$name]) ? $this->arguments[$name] : $default;
        } else {
            return isset($this->argoptions[$name]) ? $this->argoptions[$name] : $default;
        }
    }

}
