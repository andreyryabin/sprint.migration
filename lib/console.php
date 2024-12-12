<?php

namespace Sprint\Migration;

use Bitrix\Main\EventManager;
use CGroup;
use CUser;
use Exception;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Traits\CurrentUserTrait;
use Throwable;

class Console
{
    private $script;
    private $command;
    private $arguments  = [];
    private $versionConfig;
    private $versionManager;
    private $argoptions = [];
    use CurrentUserTrait;

    /**
     * Console constructor.
     *
     * @param $args
     *
     * @throws Exception
     */
    public function __construct($args)
    {
        $this->script = array_shift($args);

        $this->command = $this->initializeArgs($args);

        $this->versionConfig = new VersionConfig($this->getArg('--config='));
        $this->versionManager = new VersionManager($this->versionConfig);

        $this->disableAuthHandlersIfNeed();

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
            $this->commandInfo();
        } elseif (method_exists($this, $this->command)) {
            call_user_func([$this, $this->command]);
        } else {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_COMMAND_NOT_FOUND', [
                        '#NAME#' => $this->command,
                    ]
                )
            );
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

        $by = 'c_sort';
        $order = 'asc';

        $groupitem = CGroup::GetList($by, $order, [
            'ADMIN'  => 'Y',
            'ACTIVE' => 'Y',
        ])->Fetch();

        if (!empty($groupitem)) {
            $by = 'id';

            $useritem = CUser::GetList($by, $order, [
                'GROUPS_ID' => [$groupitem['ID']],
                'ACTIVE'    => 'Y',
            ], [
                'NAV_PARAMS' => ['nTopCount' => 1],
            ])->Fetch();

            if (!empty($useritem)) {
                $USER->Authorize($useritem['ID']);
            }
        }
    }

    /**
     * @noinspection PhpUnused
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
            'prefix'      => $prefix,
        ]);
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandMark()
    {
        $search = $this->getArg(0);
        $status = $this->getArg('--as=');

        if ($search && $status) {
            Out::outMessages(
                $this->versionManager->markMigration($search, $status)
            );
        } else {
            throw new MigrationException(
                Locale::getMessage('ERR_INVALID_ARGUMENTS')
            );
        }
    }

    /**
     * @throws MigrationException
     */
    public function commandDelete()
    {
        Out::outMessages(
            $this->versionManager->deleteMigration($this->getArg(0))
        );
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandDel()
    {
        $this->commandDelete();
    }

    /**
     * @throws MigrationException
     */
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
            'status'   => $status,
            'search'   => $this->getArg('--search='),
            'tag'      => $this->getArg('--tag='),
            'modified' => $this->getArg('--modified'),
            'older'    => $this->getArg('--older'),
            'actual'   => $this->getArg('--actual'),
        ]);

        if ($status) {
            $summary = [];
            $summary[$status] = 0;
        } else {
            $summary = [
                VersionEnum::STATUS_NEW       => 0,
                VersionEnum::STATUS_INSTALLED => 0,
                VersionEnum::STATUS_UNKNOWN   => 0,
            ];
        }

        $grid = new ConsoleGrid(-1, [
            'horizontal'   => '=',
            'vertical'     => '',
            'intersection' => '',
        ], 1, 'UTF-8');

        $grid->setHeaders([
            'Version',
            'Status',
            'Description',
        ]);

        foreach ($versions as $item) {
            $versionColumn = $item['version'];

            $labelsColumn = '';
            if ($item['tag']) {
                $labelsColumn .= ' (' . $item['tag'] . ')';
            }
            if ($item['older']) {
                $labelsColumn .= ' (' . $item['older'] . ' !!)';
            }
            if ($item['modified']) {
                $labelsColumn .= ' (' . Locale::getMessage('MODIFIED_LABEL') . ')';
            }

            $descrColumn = Out::prepareToConsole(
                $item['description'],
                [
                    'max_len'          => 50,
                    'tracker_task_url' => $this->versionConfig->getVal('tracker_task_url'),
                ]
            );

            $statusColumn = Locale::getMessage('META_' . $item['status']);

            $grid->addRow([$versionColumn, $statusColumn . $labelsColumn, $descrColumn]);

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
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandUp()
    {
        if ($this->hasArguments()) {
            foreach ($this->getArguments() as $versionName) {
                $this->executeOnce($versionName, VersionEnum::ACTION_UP);
            }
        } else {
            $this->executeAll([
                'search'   => $this->getArg('--search='),
                'tag'      => $this->getArg('--tag='),
                'modified' => $this->getArg('--modified'),
                'older'    => $this->getArg('--older'),
                'actual'   => $this->getArg('--actual'),
            ], VersionEnum::ACTION_UP);
        }
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandDown()
    {
        if ($this->hasArguments()) {
            foreach ($this->getArguments() as $versionName) {
                $this->executeOnce($versionName, VersionEnum::ACTION_DOWN);
            }
        } else {
            $this->executeAll([
                'search'   => $this->getArg('--search='),
                'tag'      => $this->getArg('--tag='),
                'modified' => $this->getArg('--modified'),
                'older'    => $this->getArg('--older'),
                'actual'   => $this->getArg('--actual'),
            ], VersionEnum::ACTION_DOWN);
        }
    }

    /**
     * @noinspection PhpUnused
     */
    public function commandRedo()
    {
        foreach ($this->getArguments() as $versionName) {
            $this->executeVersion($versionName, VersionEnum::ACTION_DOWN);
            $this->executeVersion($versionName, VersionEnum::ACTION_UP);
        }
    }

    /**
     *
     */
    public function commandInfo()
    {
        Out::out(
            Locale::getMessage('MODULE_NAME')
        );
        Out::out(
            Locale::getMessage('BITRIX_VERSION') . ': %s',
            defined('SM_VERSION') ? SM_VERSION : ''
        );
        Out::out(
            Locale::getMessage('MODULE_VERSION') . ': %s',
            Module::getVersion()
        );
        Out::out(
            Locale::getMessage('CURRENT_USER') . ': [%d] %s',
            $this->getCurrentUserId(),
            $this->getCurrentUserLogin()
        );

        $configList = $this->versionConfig->getList();
        $configName = $this->versionConfig->getName();

        Out::out('');
        Out::out(Locale::getMessage('CONFIG_LIST') . ':');
        foreach ($configList as $configItem) {
            if ($configItem['name'] == $configName) {
                Out::out('  ' . $configItem['title'] . ' *');
            } else {
                Out::out('  ' . $configItem['title']);
            }
        }
        Out::out('');
        Out::out(
            Locale::getMessage('COMMAND_CONFIG') . ':' . PHP_EOL . '  php %s config' . PHP_EOL,
            $this->script
        );
        Out::out(
            Locale::getMessage('COMMAND_RUN') . ':' . PHP_EOL . '  php %s <command> [<args>]' . PHP_EOL,
            $this->script
        );
        Out::out(
            Locale::getMessage('COMMAND_HELP') . ':' . PHP_EOL . '  php %s help' . PHP_EOL,
            $this->script
        );
    }

    /**
     * @noinspection PhpUnused
     */
    public function commandHelp()
    {
        if (Locale::getLang() == 'en') {
            Out::out(file_get_contents(Module::getModuleDir() . '/commands-en.txt'));
        } else {
            Out::out(file_get_contents(Module::getModuleDir() . '/commands.txt'));
        }
    }

    public function commandConfig()
    {
        $configValues = $this->versionConfig->getCurrent('values');
        $configTitle = $this->versionConfig->getCurrent('title');

        $configValues = $this->versionConfig->humanValues($configValues);

        Out::out(
            '%s: %s',
            Locale::getMessage('CONFIG'),
            $configTitle
        );

        $grid = new ConsoleGrid(-1, [
            'horizontal'   => '=',
            'vertical'     => '',
            'intersection' => '',
        ], 1, 'UTF-8');

        $grid->setBorderVisibility(['bottom' => false]);

        foreach ($configValues as $key => $val) {
            $grid->addRow([$key, $val]);
        }

        Out::out($grid->build());
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandLs()
    {
        $this->commandList();
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandAdd()
    {
        $this->commandCreate();
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandMigrate()
    {
        $this->executeAll([
            'search'   => $this->getArg('--search='),
            'tag'      => $this->getArg('--tag='),
            'modified' => $this->getArg('--modified'),
            'older'    => $this->getArg('--older'),
            'actual'   => $this->getArg('--actual'),
        ], $this->getArg('--down') ? VersionEnum::ACTION_DOWN : VersionEnum::ACTION_UP);
    }

    /**
     * @noinspection PhpUnused
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
        foreach ($this->getArguments() as $versionName) {
            if ($this->getArg('--down')) {
                $this->executeOnce($versionName, VersionEnum::ACTION_DOWN);
            } else {
                $this->executeOnce($versionName, VersionEnum::ACTION_UP);
            }
        }
    }

    /**
     * @noinspection PhpUnused
     * @throws Exception
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
                'title'    => 'select schemas',
                'select'   => $selectValues,
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
            } catch (Throwable $e) {
                Out::outException($e);
            }
        } while ($restart == 1);

        return true;
    }

    /**
     * @throws MigrationException
     */
    protected function executeAll($filter, $action)
    {
        $success = 0;
        $fails = 0;

        $versionNames = $this->versionManager->getListForExecute($filter, $action);

        foreach ($versionNames as $versionName) {
            $ok = $this->executeVersion($versionName, $action);

            if ($ok) {
                $success++;
            } else {
                $fails++;
            }

            if ($fails) {
                break;
            }
        }

        Out::out('migrations (%s): %d', $action, $success);

        if ($fails) {
            throw new MigrationException(
                Locale::getMessage('ERR_SOME_MIGRATIONS_FAILS')
            );
        }
    }

    /**
     * @throws MigrationException
     */
    protected function executeOnce($version, $action)
    {
        $ok = $this->executeVersion($version, $action);

        if (!$ok) {
            throw new MigrationException(
                Locale::getMessage('ERR_MIGRATION_FAIL')
            );
        }
    }

    protected function executeVersion($version, $action)
    {
        $tag = $this->getArg('--add-tag=', '');

        $params = [];

        Out::out('%s (%s) start', $version, $action);

        do {
            $exec = 0;

            $success = $this->versionManager->startMigration(
                $version,
                $action,
                $params,
                $tag
            );

            $restart = $this->versionManager->needRestart();

            if ($restart) {
                $params = $this->versionManager->getRestartParams();
                $exec = 1;
            }

            if ($success && !$restart) {
                Out::out('%s (%s) success', $version, $action);
            }

            if (!$success && !$restart) {
                Out::outException($this->versionManager->getLastException());
            }
        } while ($exec == 1);

        return $success;
    }

    /**
     * @throws MigrationException
     */
    protected function executeBuilder($from, $postvars = [])
    {
        do {
            $builder = $this->versionManager->createBuilder($from, $postvars);

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
        [$name, $val] = explode('=', $arg);
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
            return $this->arguments[$name] ?? $default;
        } else {
            return $this->argoptions[$name] ?? $default;
        }
    }

    protected function getArguments()
    {
        return $this->arguments;
    }

    protected function hasArguments()
    {
        return !empty($this->arguments);
    }

    private function disableAuthHandlersIfNeed()
    {
        if ($this->versionConfig->getVal('console_auth_events_disable')) {
            $this->disableHandler('main', 'OnAfterUserAuthorize');
            $this->disableHandler('main', 'OnUserLogin');
        }
    }

    private function disableHandler($moduleId, $eventType)
    {
        $eventManager = EventManager::getInstance();
        $handlers = $eventManager->findEventHandlers($moduleId, $eventType);
        foreach ($handlers as $iEventHandlerKey => $handler) {
            $eventManager->removeEventHandler($moduleId, $eventType, $iEventHandlerKey);
        }
    }
}
