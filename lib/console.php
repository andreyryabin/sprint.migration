<?php

namespace Sprint\Migration;

use Bitrix\Main\EventManager;
use CGroup;
use CUser;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Traits\CurrentUserTrait;

class Console
{
    private string $script;
    private string $command;
    private array $arguments = [];
    private VersionConfig $versionConfig;
    private VersionManager $versionManager;
    private array $argoptions = [];
    use CurrentUserTrait;

    /**
     * @throws MigrationException
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
        } elseif (str_starts_with($userlogin, 'login:')) {
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
            'ADMIN' => 'Y',
            'ACTIVE' => 'Y',
        ])->Fetch();

        if (!empty($groupitem)) {
            $by = 'id';

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
            'prefix' => $prefix,
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
            'status' => $status,
            'search' => $this->getArg('--search='),
            'tag' => $this->getArg('--tag='),
            'modified' => $this->getArg('--modified'),
            'older' => $this->getArg('--older'),
            'actual' => $this->getArg('--actual'),
        ]);

        $summary = [
            VersionEnum::STATUS_NEW => 0,
            VersionEnum::STATUS_INSTALLED => 0,
            VersionEnum::STATUS_UNKNOWN => 0,
        ];

        foreach ($versions as $item) {
            $versionLabels = [];
            if ($item['older']) {
                $olderMsg = Locale::getMessage('OLDER_VERSION', [
                    '#V1#' => $item['older'],
                ]);
                $versionLabels[] = '[label:red]' . $olderMsg . '[/]';
            }
            if ($item['modified']) {
                $versionLabels[] = '[label:yellow]' . Locale::getMessage('MODIFIED_VERSION') . '[/]';
            }
            if ($item['status'] == VersionEnum::STATUS_UNKNOWN) {
                $versionLabels[] = '[label]' . Locale::getMessage('VERSION_UNKNOWN') . '[/]';
            }
            $descrColumn = Out::prepareToConsole(
                $item['description'],
                [
                    'tracker_task_url' => $this->versionConfig->getVal('tracker_task_url'),
                ]
            );

            Out::out('┌─');
            Out::out('│ [%s]%s[/]', $item['status'], $item['version']);

            if ($item['file_status']) {
                Out::out('│ ' . $item['file_status']);
            }

            if ($item['record_status']) {
                Out::out('│ ' . $item['record_status']);
            }

            if ($item['tag']) {
                $tagMsg = Locale::getMessage('RELEASE_TAG', [
                    '#TAG#' => '[label:green]' . $item['tag'] . '[/]',
                ]);
                Out::out('│ ' . $tagMsg);
            }

            if (!empty($versionLabels)) {
                Out::out('│ ' . implode(' ', $versionLabels));
            }

            if ($descrColumn) {
                Out::out('├─');
                $descrColumn = explode(PHP_EOL, $descrColumn);
                foreach ($descrColumn as $descStr) {
                    Out::out('│ ' . $descStr);
                }
            }

            Out::out('└─');

            $stval = $item['status'];
            $summary[$stval]++;
        }

        foreach ($summary as $k => $v) {
            if ($v > 0) {
                Out::out(Locale::getMessage('META_' . $k) . ':' . $v);
            }
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

        foreach ($configValues as $configKey => $configValue) {
            Out::out('┌─');
            Out::out('│ ' . Locale::getMessage('CONFIG_' . $configKey));
            Out::out('│ ' . $configKey);

            if ($configValue) {
                Out::out('├─');
                $configValue = explode(PHP_EOL, $configValue);
                foreach ($configValue as $valueStr) {
                    Out::out('│ ' . $valueStr);
                }
            }

            Out::out('└─');
        }
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
                'search' => $this->getArg('--search='),
                'tag' => $this->getArg('--tag='),
                'modified' => $this->getArg('--modified'),
                'older' => $this->getArg('--older'),
                'actual' => $this->getArg('--actual'),
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
                'search' => $this->getArg('--search='),
                'tag' => $this->getArg('--tag='),
                'modified' => $this->getArg('--modified'),
                'older' => $this->getArg('--older'),
                'actual' => $this->getArg('--actual'),
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
            'search' => $this->getArg('--search='),
            'tag' => $this->getArg('--tag='),
            'modified' => $this->getArg('--modified'),
            'older' => $this->getArg('--older'),
            'actual' => $this->getArg('--actual'),
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
    protected function executeOnce(string $version, string $action)
    {
        $ok = $this->executeVersion($version, $action);

        if (!$ok) {
            throw new MigrationException(
                Locale::getMessage('ERR_MIGRATION_FAIL')
            );
        }
    }

    protected function executeVersion(string $version, string $action): bool
    {
        $tag = $this->getArg('--add-tag=');

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
    protected function executeBuilder(string $from, array $postvars = [])
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

    protected function initializeArgs(array $args): string
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

        if (str_starts_with($name, '--')) {
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

    private function disableHandler(string $moduleId, string $eventType)
    {
        $eventManager = EventManager::getInstance();
        $handlers = $eventManager->findEventHandlers($moduleId, $eventType);
        foreach ($handlers as $iEventHandlerKey => $handler) {
            $eventManager->removeEventHandler($moduleId, $eventType, $iEventHandlerKey);
        }
    }
}
