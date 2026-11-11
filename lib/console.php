<?php

namespace Sprint\Migration;

use Bitrix\Main\EventManager;
use CGroup;
use CUser;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Output\ConsoleOutput;
use Sprint\Migration\Traits\CurrentUserTrait;

class Console
{
    private string $script;
    private string $command;
    private array $arguments = [];
    private VersionConfig $versionConfig;
    private VersionManager $versionManager;
    private ConsoleOutput $output;
    private array $argoptions = [];
    private BuilderManager $builderManager;

    use CurrentUserTrait;

    /**
     * @throws MigrationException
     */
    public function __construct($args)
    {
        $this->script = array_shift($args);

        $this->command = $this->initializeArgs($args);

        $this->versionConfig = ConfigManager::getInstance()->get(
            $this->getArg('--config=', VersionEnum::CONFIG_DEFAULT)
        );

        $this->output = new ConsoleOutput();

        $this->versionManager = new VersionManager(
            $this->versionConfig,
            $this->output
        );

        $this->builderManager = new BuilderManager(
            $this->versionConfig,
            $this->output
        );

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
    public function executeConsoleCommand(): void
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

    public function authorizeAsLogin($login): void
    {
        global $USER;
        $dbres = CUser::GetByLogin($login);
        $useritem = $dbres->Fetch();
        if ($useritem) {
            $USER->Authorize($useritem['ID']);
        }
    }

    public function authorizeAsAdmin(): void
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
     * @throws BuilderException
     */
    public function commandRun(): void
    {
        $this->executeBuilder($this->getArg(0));
    }

    /**
     * @throws BuilderException
     */
    public function commandCreate(): void
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
    public function commandMark(): void
    {
        $version = $this->getArg(0);
        $status = $this->getArg('--as=');

        if ($version && $status) {
            $this->versionManager->markMigration($version, $status);
        } else {
            throw new MigrationException(
                Locale::getMessage('ERR_INVALID_ARGUMENTS')
            );
        }
    }

    /**
     * @throws MigrationException
     */
    public function commandDelete(): void
    {
        $this->versionManager->deleteMigration($this->getArg(0));
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandDel(): void
    {
        $this->commandDelete();
    }

    /**
     * @throws MigrationException
     */
    public function commandList(): void
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

        $summary = [
            VersionEnum::STATUS_NEW       => 0,
            VersionEnum::STATUS_INSTALLED => 0,
            VersionEnum::STATUS_UNKNOWN   => 0,
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

            $this->output->print('┌─');
            $this->output->print('│ [%s]%s[/]', $item['status'], $item['version']);

            if ($item['file_status']) {
                $this->output->print('│ ' . $item['file_status']);
            }

            if ($item['record_status']) {
                $this->output->print('│ ' . $item['record_status']);
            }

            if ($item['tag_status']) {
                $this->output->print('│ ' . $item['tag_status']);
            }

            if (!empty($versionLabels)) {
                $this->output->print('│ ' . implode(' ', $versionLabels));
            }

            if (!empty($item['description'])) {
                $this->output->print('├─');
                foreach (explode(PHP_EOL, $item['description']) as $descStr) {
                    $this->output->print('│ ' . $descStr);
                }
            }

            $this->output->print('└─');

            $stval = $item['status'];
            $summary[$stval]++;
        }

        foreach ($summary as $k => $v) {
            if ($v > 0) {
                $this->output->print(Locale::getMessage('META_' . $k) . ':' . $v);
            }
        }
    }

    /**
     * @noinspection PhpUnused
     */
    public function commandConfig(): void
    {
        $this->output->print(
            '%s: %s',
            Locale::getMessage('CONFIG'),
            $this->versionConfig->getTitle()
        );

        foreach ($this->versionConfig->humanValues() as $configKey => $configValue) {
            $this->output->print('┌─');
            $this->output->print('│ ' . Locale::getMessage('CONFIG_' . $configKey));
            $this->output->print('│ ' . $configKey);

            if ($configValue) {
                $this->output->print('├─');
                $configValue = explode(PHP_EOL, $configValue);
                foreach ($configValue as $valueStr) {
                    $this->output->print('│ ' . $valueStr);
                }
            }

            $this->output->print('└─');
        }
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandUp(): void
    {
        $this->executeByArgs(VersionEnum::ACTION_UP);
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandDown(): void
    {
        $this->executeByArgs(VersionEnum::ACTION_DOWN);
    }

    /**
     * @throws MigrationException
     */
    protected function executeByArgs($action): void
    {
        $tag = $this->getArg('--add-tag=');

        if ($this->hasArguments()) {
            foreach ($this->getArguments() as $versionName) {
                if (!$this->executeVersion($versionName, $action, $tag)) {
                    return;
                }
            }
            return;
        }

        $this->executeAll([
            'search'   => $this->getArg('--search='),
            'tag'      => $this->getArg('--tag='),
            'modified' => $this->getArg('--modified'),
            'older'    => $this->getArg('--older'),
            'actual'   => $this->getArg('--actual'),
        ], $action, $tag);
    }

    /**
     * @noinspection PhpUnused
     */
    public function commandRedo(): void
    {
        $tag = $this->getArg('--add-tag=');

        foreach ($this->getArguments() as $versionName) {
            if (!$this->executeVersion($versionName, VersionEnum::ACTION_DOWN, $tag)
                || !$this->executeVersion($versionName, VersionEnum::ACTION_UP, $tag)) {
                return;
            }
        }
    }

    public function commandInfo(): void
    {
        $this->output->print(
            Locale::getMessage('MODULE_NAME')
        );
        $this->output->print(
            Locale::getMessage('MODULE_VERSION') . ': %s',
            Module::getVersion()
        );
        $this->output->print(
            Locale::getMessage('PHP_VERSION') . ': %s',
            defined('PHP_VERSION') ? PHP_VERSION : ''
        );
        $this->output->print(
            Locale::getMessage('BITRIX_VERSION') . ': %s',
            defined('SM_VERSION') ? SM_VERSION : ''
        );
        $this->output->print(
            Locale::getMessage('CURRENT_USER') . ': [%d] %s',
            $this->getCurrentUserId(),
            $this->getCurrentUserLogin()
        );

        $this->output->print('');
        $this->output->print(Locale::getMessage('CONFIG_LIST') . ':');

        foreach (ConfigManager::getInstance()->getList() as $configItem) {
            if ($configItem->getName() == $this->versionConfig->getName()) {
                $this->output->print('  ' . $configItem->getTitle() . ' *');
            } else {
                $this->output->print('  ' . $configItem->getTitle());
            }
        }
        $this->output->print('');
        $this->output->print(
            Locale::getMessage('COMMAND_CONFIG') . ':' . PHP_EOL . '  php %s config' . PHP_EOL,
            $this->script
        );
        $this->output->print(
            Locale::getMessage('COMMAND_RUN') . ':' . PHP_EOL . '  php %s <command> [<args>]' . PHP_EOL,
            $this->script
        );
        $this->output->print(
            Locale::getMessage('COMMAND_HELP') . ':' . PHP_EOL . '  php %s help' . PHP_EOL,
            $this->script
        );
    }

    /**
     * @noinspection PhpUnused
     */
    public function commandHelp(): void
    {
        if (Locale::getDefaultLang() == 'en') {
            $this->output->print(file_get_contents(Module::getModuleDir() . '/commands-en.txt'));
        } else {
            $this->output->print(file_get_contents(Module::getModuleDir() . '/commands.txt'));
        }
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandLs(): void
    {
        $this->commandList();
    }

    /**
     * @noinspection PhpUnused
     * @throws BuilderException
     */
    public function commandAdd(): void
    {
        $this->commandCreate();
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandMigrate(): void
    {
        $tag = $this->getArg('--add-tag=');
        $action = $this->getArg('--down') ? VersionEnum::ACTION_DOWN : VersionEnum::ACTION_UP;

        $this->executeAll([
            'search'   => $this->getArg('--search='),
            'tag'      => $this->getArg('--tag='),
            'modified' => $this->getArg('--modified'),
            'older'    => $this->getArg('--older'),
            'actual'   => $this->getArg('--actual'),
        ], $action, $tag);
    }

    /**
     * @noinspection PhpUnused
     * @throws MigrationException
     */
    public function commandMi(): void
    {
        /** @compability */
        $this->commandMigrate();
    }

    /**
     * @throws MigrationException
     */
    protected function executeAll($filter, $action, $tag): void
    {
        $versionNames = $this->versionManager->getListForExecute($filter, $action);
        foreach ($versionNames as $versionName) {
            if (!$this->executeVersion($versionName, $action, $tag)) {
                return;
            }
        }
    }

    protected function executeVersion(string $version, string $action, string $tag): bool
    {
        $params = [];

        do {
            $result = $this->versionManager->startMigration(
                $version,
                $action,
                $params,
                $tag
            );

            $params = $result->getRestartParams();

        } while ($result->needRestart());

        return $result->isSuccess();
    }

    /**
     * @throws BuilderException
     */
    protected function executeBuilder(string $from, array $postvars = []): void
    {
        do {
            $builder = $this->builderManager->createBuilder($from, $postvars);

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

    protected function addArg($arg): void
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

    protected function getArguments(): array
    {
        return $this->arguments;
    }

    protected function hasArguments(): bool
    {
        return !empty($this->arguments);
    }

    private function disableAuthHandlersIfNeed(): void
    {
        if ($this->versionConfig->getVal('console_auth_events_disable')) {
            $this->disableHandler('main', 'OnAfterUserAuthorize');
            $this->disableHandler('main', 'OnUserLogin');
        }
    }

    private function disableHandler(string $moduleId, string $eventType): void
    {
        $eventManager = EventManager::getInstance();
        $handlers = $eventManager->findEventHandlers($moduleId, $eventType);
        foreach ($handlers as $iEventHandlerKey => $handler) {
            $eventManager->removeEventHandler($moduleId, $eventType, $iEventHandlerKey);
        }
    }
}
