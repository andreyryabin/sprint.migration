<?php

namespace Sprint\Migration;

use CGroup;
use CUser;
use Exception;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Throwable;

class Console
{

    private $script = 'migrate.php';
    private $command = '';

    private $arguments = [];

    private $versionConfig;
    private $versionManager;

    private $argoptions = [];

    /**
     * Console constructor.
     * @param $args
     * @throws Exception
     */
    public function __construct($args)
    {
        $this->script = array_shift($args);

        $this->command = $this->initializeArgs($args);

        $this->versionConfig = new VersionConfig($this->getArg('--config='));
        $this->versionManager = new VersionManager($this->versionConfig);

        $userlogin = $this->versionConfig->getVal('console_user');
        if ($userlogin == 'admin') {
            $this->authorizeAsAdmin();
        } elseif (strpos($userlogin, 'login:') === 0) {
            $userlogin = substr($userlogin, 6);
            $this->authorizeAsLogin($userlogin);
        }

    }

    /**
     * @throws MigrationException
     */
    public function executeConsoleCommand()
    {
        if (empty($this->command)) {
            $this->commandHelp();

        } elseif (method_exists($this, $this->command)) {
            call_user_func([$this, $this->command]);

        } else {
            $this->exitWithMessage(sprintf('Command "%s" not found, see help', $this->command));
        }
    }

    public function authorizeAsLogin($login)
    {
        global $USER;
        $dbres = CUser::GetByLogin($login);
        $useritem = $dbres->Fetch();
        if ($useritem) {
            $USER->Authorize($useritem['ID']);
        }
    }

    public function authorizeAsAdmin()
    {
        global $USER;

        $groupitem = CGroup::GetList($by, $order, [
            'ADMIN' => 'Y',
            'ACTIVE' => 'Y',
        ])->Fetch();

        if (!empty($groupitem)) {
            $by = 'id';
            $order = 'asc';

            $useritem = CUser::GetList($by, $order, [
                'GROUPS_ID' => [$groupitem['ID']],
                'ACTIVE' => 'Y',
            ], [
                'NAV_PARAMS' => ['nTopCount' => 1],
            ])->Fetch();

            if (!empty($useritem)) {
                $USER->Authorize($useritem['ID']);
            }
        }

    }

    /**
     * @throws MigrationException
     */
    public function commandRun()
    {
        $this->executeBuilder($this->getArg(0));
    }

    /**
     * @throws MigrationException
     */
    public function commandCreate()
    {
        /** @compability */
        $descr = $this->getArg(0);
        /** @compability */
        $prefix = $this->getArg(1);

        /** @compability */
        $prefix = $this->getArg('--prefix=', $prefix);

        $prefix = $this->getArg('--name=', $prefix);
        $descr = $this->getArg('--desc=', $descr);

        $from = $this->getArg('--from=', 'BlankBuilder');

        $this->executeBuilder($from, [
            'description' => $descr,
            'prefix' => $prefix,
        ]);
    }

    /**
     * @throws MigrationException
     */
    public function commandMark()
    {
        $search = $this->getArg(0);
        $status = $this->getArg('--as=');

        if ($search && $status) {
            $markresult = $this->versionManager->markMigration($search, $status);
            foreach ($markresult as $val) {
                Out::out($val['message']);
            }
        } else {
            $this->exitWithMessage('Invalid arguments, see help');
        }
    }

    public function commandDelete()
    {
        $results = $this->versionManager->deleteMigration($this->getArg(0));

        foreach ($results as $result) {
            if ($result['success']) {
                Out::outSuccess($result['message']);
            } else {
                Out::outError($result['message']);
            }
        }
    }

    public function commandDel()
    {
        $this->commandDelete();
    }

    public function commandList()
    {
        if ($this->getArg('--new')) {
            $status = VersionEnum::STATUS_NEW;
        } elseif ($this->getArg('--installed')) {
            $status = VersionEnum::STATUS_INSTALLED;
        } else {
            $status = '';
        }

        $versions = $this->versionManager->getVersions([
            'status' => $status,
            'search' => $this->getArg('--search='),
            'tag' => $this->getArg('--tag='),
        ]);

        if ($status) {
            $summary = [];
            $summary[$status] = 0;
        } else {
            $summary = [
                VersionEnum::STATUS_NEW => 0,
                VersionEnum::STATUS_INSTALLED => 0,
                VersionEnum::STATUS_UNKNOWN => 0,
            ];
        }

        $grid = new ConsoleGrid(-1, [
            'horizontal' => '=',
            'vertical' => '',
            'intersection' => '',
        ], 1, 'UTF-8');

        $grid->setHeaders([
            'Version',
            'Status',
            'Tag',
            'Description',
        ]);

        foreach ($versions as $index => $item) {
            if ($item['modified']) {
                $item['version'] .= ' (' . Locale::getMessage('MODIFIED_LABEL') . ')';
            }

            $grid->addRow([
                $item['version'],
                Locale::getMessage('META_' . strtoupper($item['status'])),
                $item['tag'],
                $item['description'],
            ]);

            $stval = $item['status'];
            $summary[$stval]++;
        }

        Out::out($grid->build());

        $grid = new ConsoleGrid(-1, '', 1, 'UTF-8');
        foreach ($summary as $k => $v) {
            $grid->addRow([Locale::getMessage('META_' . strtoupper($k)) . ':', $v,]);
        }

        Out::out($grid->build());

    }

    /**
     * @throws MigrationException
     */
    public function commandUp()
    {
        $versionName = $this->getArg(0);

        if (is_numeric($versionName)) {
            /** @deprecated */
            $this->exitWithMessage('limit is no longer supported');
        }

        if ($this->versionManager->checkVersionName($versionName)) {
            $this->executeOnce($versionName, VersionEnum::ACTION_UP);
        } else {
            $this->executeAll([
                'status' => VersionEnum::STATUS_NEW,
                'search' => $this->getArg('--search='),
                'tag' => $this->getArg('--tag='),
            ]);
        }
    }

    /**
     * @throws MigrationException
     */
    public function commandDown()
    {
        $versionName = $this->getArg(0);

        if (is_numeric($versionName)) {
            /** @deprecated */
            $this->exitWithMessage('limit is no longer supported');
        }

        if ($this->versionManager->checkVersionName($versionName)) {
            $this->executeOnce($versionName, VersionEnum::ACTION_DOWN);
        } else {
            $this->executeAll([
                'status' => VersionEnum::STATUS_INSTALLED,
                'search' => $this->getArg('--search='),
                'tag' => $this->getArg('--tag='),
            ]);
        }
    }

    /**
     * @throws MigrationException
     */
    public function commandRedo()
    {
        $version = $this->getArg(0);
        if ($version) {
            $this->executeVersion($version, VersionEnum::ACTION_DOWN);
            $this->executeVersion($version, VersionEnum::ACTION_UP);
        } else {
            $this->exitWithMessage('Version not found!');
        }
    }

    public function commandHelp()
    {
        global $USER;

        Out::out(Locale::getMessage('MODULE_NAME'));
        Out::out('Версия bitrix: %s', defined('SM_VERSION') ? SM_VERSION : '');
        Out::out('Версия модуля: %s', Module::getVersion());

        if ($USER && $USER->GetID()) {
            Out::out('Текущий пользователь: [%d] %s', $USER->GetID(), $USER->GetLogin());
        }

        $configList = $this->versionConfig->getList();
        $configName = $this->versionConfig->getName();

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

    public function commandConfig()
    {
        $configValues = $this->versionConfig->getCurrent('values');
        $configTitle = $this->versionConfig->getCurrent('title');

        $configValues = $this->versionConfig->humanValues($configValues);

        Out::out('%s: %s',
            Locale::getMessage('CONFIG'),
            $configTitle
        );

        $grid = new ConsoleGrid(-1, [
            'horizontal' => '=',
            'vertical' => '',
            'intersection' => '',
        ], 1, 'UTF-8');

        $grid->setBorderVisibility(['bottom' => false]);

        foreach ($configValues as $key => $val) {
            $grid->addRow([$key, $val]);
        }

        Out::out($grid->build());


    }

    public function commandLs()
    {
        $this->commandList();
    }

    /**
     * @throws MigrationException
     */
    public function commandAdd()
    {
        $this->commandCreate();
    }

    /**
     * @throws MigrationException
     */
    public function commandMigrate()
    {
        /** @compability */
        $status = $this->getArg('--down') ? VersionEnum::STATUS_INSTALLED : VersionEnum::STATUS_NEW;
        $this->executeAll([
            'status' => $status,
            'search' => $this->getArg('--search='),
            'tag' => $this->getArg('--tag='),
        ]);
    }

    /**
     * @throws MigrationException
     */
    public function commandMi()
    {
        /** @compability */
        $this->commandMigrate();
    }

    /**
     * @throws MigrationException
     */
    public function commandExecute()
    {
        /** @compability */
        $version = $this->getArg(0);
        if ($version) {
            if ($this->getArg('--down')) {
                $this->executeOnce($version, VersionEnum::ACTION_DOWN);
            } else {
                $this->executeOnce($version, VersionEnum::ACTION_UP);
            }
        } else {
            $this->exitWithMessage('Version not found!');
        }
    }

    /**
     * @throws MigrationException
     */
    public function commandForce()
    {
        /** @compability */
        $this->addArg('--force');
        $this->commandExecute();
    }

    /**
     * @return bool
     */
    public function commandSchema()
    {
        $action = $this->getArg(0);

        $schemaManager = new SchemaManager($this->versionConfig);
        $enabledSchemas = $schemaManager->getEnabledSchemas();

        $selectValues = [];
        foreach ($enabledSchemas as $schema) {
            $selectValues[] = [
                'value' => $schema->getName(),
                'title' => $schema->getTitle(),
            ];
        }

        if (empty($action)) {
            foreach ($enabledSchemas as $schema) {
                $schema->outTitle(true);
                $schema->outDescription();
            }

            return true;
        }

        $select = $this->getArg(1);
        if (!empty($select)) {
            $select = explode(' ', $select);
            $select = array_filter($select, function ($a) {
                return !empty($a);
            });
        } else {
            $select = Out::input([
                'title' => 'select schemas',
                'select' => $selectValues,
                'multiple' => 1,
            ]);
        }


        $params = [];

        do {

            $schemaManager = new SchemaManager($this->versionConfig, $params);
            $restart = 0;

            try {

                if ($action == 'diff') {
                    $schemaManager->setTestMode(1);
                    $schemaManager->import(['name' => $select]);

                } elseif ($action == 'import') {
                    $schemaManager->setTestMode(0);
                    $schemaManager->import(['name' => $select]);

                } elseif ($action == 'export') {
                    $schemaManager->export(['name' => $select]);
                }


            } catch (Exceptions\RestartException $e) {
                $params = $schemaManager->getRestartParams();
                $restart = 1;

            } catch (Exception $e) {
                Out::outWarning($e->getMessage());

            } catch (Throwable $e) {
                Out::outWarning($e->getMessage());
            }

        } while ($restart == 1);

        return true;
    }

    /**
     * @param $filter
     * @throws MigrationException
     */
    protected function executeAll($filter)
    {
        $success = 0;
        $fails = 0;

        $versions = $this->versionManager->getVersions($filter);

        $action = ($filter['status'] == VersionEnum::STATUS_NEW) ? VersionEnum::ACTION_UP : VersionEnum::ACTION_DOWN;

        foreach ($versions as $item) {

            $ok = $this->executeVersion($item['version'], $action);

            if ($ok) {
                $success++;
            } else {
                $fails++;
            }

            if ($fails && $this->versionConfig->getVal('stop_on_errors')) {
                break;
            }

        }

        Out::out('migrations (%s): %d', $action, $success);

        if ($fails) {
            $this->exitWithMessage('some migrations fails');
        }
    }

    /**
     * @param $version
     * @param string $action
     * @throws MigrationException
     */
    protected function executeOnce($version, $action = VersionEnum::ACTION_UP)
    {
        $ok = $this->executeVersion($version, $action);

        if (!$ok) {
            $this->exitWithMessage('migration fail');
        }
    }

    protected function executeVersion($version, $action = VersionEnum::ACTION_UP)
    {

        $tag = $this->getArg('--add-tag=', '');
        $force = $this->getArg('--force');

        $params = [];

        Out::out('%s (%s) start', $version, $action);

        do {
            $exec = 0;

            $success = $this->versionManager->startMigration(
                $version,
                $action,
                $params,
                $force,
                $tag
            );

            $restart = $this->versionManager->needRestart($version);

            if ($restart) {
                $params = $this->versionManager->getRestartParams($version);
                $exec = 1;
            }

            if ($success && !$restart) {
                Out::out('%s (%s) success', $version, $action);
            }

            if (!$success && !$restart) {
                Out::out('%s (%s) error: %s',
                    $version,
                    $action,
                    $this->versionManager->getLastException()->getMessage()
                );
            }

        } while ($exec == 1);

        return $success;
    }

    /**
     * @param $from
     * @param array $postvars
     * @throws MigrationException
     */
    protected function executeBuilder($from, $postvars = [])
    {
        do {

            $builder = $this->versionManager->createBuilder($from, $postvars);

            if (!$builder) {
                $this->exitWithMessage('Builder not found');
            }

            $builder->renderConsole();

            $builder->buildExecute();
            $builder->buildAfter();

            $builder->renderConsole();

            $postvars = $builder->getRestartParams();

        } while ($builder->isRestart() || $builder->isRebuild());
    }


    protected function initializeArgs($args)
    {
        foreach ($args as $val) {
            $this->addArg($val);
        }

        $command = '';

        if (isset($this->arguments[0])) {
            $command = array_shift($this->arguments);

            $command = str_replace(['_', '-', ' '], '*', $command);
            $command = explode('*', $command);
            $tmp = [];
            foreach ($command as $val) {
                $tmp[] = ucfirst(strtolower($val));
            }

            $command = 'command' . implode('', $tmp);

        }

        return $command;
    }

    protected function addArg($arg)
    {
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

    protected function getArg($name, $default = '')
    {
        if (is_numeric($name)) {
            return isset($this->arguments[$name]) ? $this->arguments[$name] : $default;
        } else {
            return isset($this->argoptions[$name]) ? $this->argoptions[$name] : $default;
        }
    }

    /**
     * @param $msg
     * @throws MigrationException
     */
    protected function exitWithMessage($msg)
    {
        Out::outError($msg);

        Throw new MigrationException();
    }

}
