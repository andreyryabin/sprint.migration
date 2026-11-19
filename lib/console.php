<?php

namespace Sprint\Migration;

use Bitrix\Main\EventManager;
use CGroup;
use CUser;
use Sprint\Migration\Entity\VersionEntity;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\BuilderException;
use Sprint\Migration\Exceptions\ConsoleException;
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
     * @throws ConsoleException
     */
    public function __construct($args)
    {
        $this->script = array_shift($args);

        $this->command = $this->initializeArgs($args);

        $this->versionConfig = ConfigManager::getInstance()->get(
            $this->getArg('--config=', VersionEnum::CONFIG_DEFAULT),
        );

        $this->output = new ConsoleOutput();

        $this->versionManager = new VersionManager(
            $this->versionConfig,
            $this->output,
        );

        $this->builderManager = new BuilderManager(
            $this->versionConfig,
            $this->output,
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
     * @throws ConsoleException
     */
    public function executeConsoleCommand(): void
    {
        if (empty($this->command)) {
            $this->commandInfo();
        } elseif (method_exists($this, $this->command)) {
            call_user_func([$this, $this->command]);
        } else {
            throw new ConsoleException("Command {$this->command} not found, see help");
        }
    }

    public function commandInfo(): void
    {
        $this->output->print(Locale::getModuleIdentyMessage());
        $this->output->print(Locale::getModuleVersionMessage());
        $this->output->print(Locale::getPhpVersionMessage());
        $this->output->print(Locale::getBitrixVersionMessage());

        $this->output->print(Locale::getCurrentUserMessage(
            $this->getCurrentUserId(),
            $this->getCurrentUserLogin(),
        ));

        $this->output->print('');
        $this->output->print(Locale::getMessage('CONFIG_LIST'));

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
            $this->script,
        );
        $this->output->print(
            Locale::getMessage('COMMAND_RUN') . ':' . PHP_EOL . '  php %s <command> [<args>]' . PHP_EOL,
            $this->script,
        );
        $this->output->print(
            Locale::getMessage('COMMAND_HELP') . ':' . PHP_EOL . '  php %s help' . PHP_EOL,
            $this->script,
        );
    }

    public function commandConfig(): void
    {
        $this->output->print(
            '%s: %s',
            Locale::getMessage('CONFIG'),
            $this->versionConfig->getTitle(),
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
     * @publicApi
     * @throws BuilderException
     */
    public function commandRun(): void
    {
        $this->executeBuilder($this->getArg(0));
    }

    /**
     * @publicApi
     * @throws MigrationException
     * @throws ConsoleException
     */
    public function commandMark(): void
    {
        $status = $this->getArg('--as=');
        $tag = $this->getArg('--add-tag=');

        if (empty($status) || empty($this->getArguments())) {
            throw new ConsoleException("Invalid arguments, see help");
        }

        foreach ($this->getArguments() as $argument) {
            if ($this->versionConfig->isVersionName($argument)) {
                $entity = $this->versionManager->getVersionByName($argument);
                $this->versionManager->markMigration($entity, $status, $tag);
            } elseif ($this->versionConfig->isVersionStatus($argument)) {
                foreach ($this->getVersionsByArgs(['status' => $argument]) as $entity) {
                    $this->versionManager->markMigration($entity, $status, $tag);
                }
            }
        }
    }

    /**
     * @throws MigrationException
     */
    public function commandDelete(): void
    {
        foreach ($this->getArguments() as $argument) {
            if ($this->versionConfig->isVersionName($argument)) {
                $entity = $this->versionManager->getVersionByName($argument);
                $this->versionManager->deleteMigration($entity);
            } elseif ($this->versionConfig->isVersionStatus($argument)) {
                foreach ($this->getVersionsByArgs(['status' => $argument]) as $entity) {
                    $this->versionManager->deleteMigration($entity);
                }
            }
        }
    }

    /**
     * @publicApi
     * @throws MigrationException
     * @throws ConsoleException
     */
    public function commandSettag(): void
    {
        $tag = $this->getArg('--add-tag=');

        if (empty($tag) || empty($this->getArguments())) {
            throw new ConsoleException("Invalid arguments, see help");
        }

        foreach ($this->getArguments() as $argument) {
            if ($this->versionConfig->isVersionName($argument)) {
                $entity = $this->versionManager->getVersionByName($argument);
                $this->versionManager->setMigrationTag($entity, $tag);
            } elseif ($this->versionConfig->isVersionStatus($argument)) {
                foreach ($this->getVersionsByArgs(['status' => $argument]) as $entity) {
                    $this->versionManager->setMigrationTag($entity, $tag);
                }
            }
        }
    }

    /**
     * @publicApi
     * @throws MigrationException
     */
    public function commandUp(): void
    {
        $this->executeByArgs(VersionEnum::ACTION_UP);
    }

    /**
     * @publicApi
     * @throws MigrationException
     */
    public function commandDown(): void
    {
        $this->executeByArgs(VersionEnum::ACTION_DOWN);
    }

    /**
     * @publicApi
     * @throws MigrationException
     */
    public function commandMigrate(): void
    {
        $action = $this->getArg('--down') ? VersionEnum::ACTION_DOWN : VersionEnum::ACTION_UP;

        $this->executeByArgs($action);
    }

    /**
     * @publicApi
     * @throws MigrationException
     */
    public function commandRedo(): void
    {
        $tag = $this->getArg('--add-tag=');

        foreach ($this->getArguments() as $versionName) {
            if (!$this->versionManager->executeVersion($versionName, VersionEnum::ACTION_DOWN, $tag)
                || !$this->versionManager->executeVersion($versionName, VersionEnum::ACTION_UP, $tag)) {
                return;
            }
        }
    }

    /**
     * @publicApi
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
     * @throws MigrationException
     */
    public function commandList(): void
    {
        $versions = $this->getVersionsByArgs();

        $summary = [
            VersionEnum::STATUS_NEW       => 0,
            VersionEnum::STATUS_INSTALLED => 0,
            VersionEnum::STATUS_UNKNOWN   => 0,
        ];

        foreach ($versions as $entity) {
            $this->output->print('┌─');
            $this->output->print('│ [%s]%s[/]', $entity->getStatus(), $entity->getVersionName());

            if ($entity->hasFile()) {
                $this->output->print('│ ' . $entity->getFileStatus());
            }
            if ($entity->hasRecord()) {
                $this->output->print('│ ' . $entity->getRecordStatus());
            }

            if ($entity->hasRecord() && $entity->getReleaseTag()) {
                $this->output->print('│ ' . $entity->getReleaseMessage());
            }

            if ($entity->getLabels()) {
                $this->output->print('│ ' . $entity->getLabels());
            }

            if ($entity->hasFile() && $entity->getFileDescription()) {
                $this->output->print('├─');
                foreach (explode(PHP_EOL, $entity->getFileDescription()) as $descStr) {
                    $this->output->print('│ ' . $descStr);
                }
            }

            $this->output->print('└─');
            $summary[$entity->getStatus()]++;
        }

        foreach (array_filter($summary) as $k => $v) {
            $this->output->print(Locale::getMessage(strtoupper('SUMMARY_' . $k)) . ':' . $v);
        }
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
     * @publicApi
     * @throws BuilderException
     */
    public function commandAdd(): void
    {
        $this->commandCreate();
    }
    /**
     * @publicApi
     * @throws MigrationException
     */
    public function commandLs(): void
    {
        $this->commandList();
    }
    /**
     * @throws MigrationException
     * @publicApi
     */
    public function commandDel(): void
    {
        $this->commandDelete();
    }
    /**
     * @publicApi
     * @throws MigrationException
     */
    public function commandMi(): void
    {
        /** @compability */
        $this->commandMigrate();
    }

    private function initializeArgs(array $args): string
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

    private function addArg($arg): void
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

    private function getArg($name, $default = '')
    {
        if (is_numeric($name)) {
            return $this->arguments[$name] ?? $default;
        } else {
            return $this->argoptions[$name] ?? $default;
        }
    }

    /**
     * @throws BuilderException
     */
    private function executeBuilder(string $from, array $postvars = []): void
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

    /**
     * @throws MigrationException
     */
    private function executeByArgs($action): void
    {
        $tag = $this->getArg('--add-tag=');

        if ($this->hasArguments()) {
            foreach ($this->getArguments() as $versionName) {
                if (!$this->versionManager->executeVersion($versionName, $action, $tag)) {
                    return;
                }
            }
            return;
        }

        $this->versionManager->executeAll(
            $this->getVersionsFilterByArgs(),
            $action,
            $tag,
        );
    }

    /**
     * @return VersionEntity[]
     * @throws MigrationException
     */
    private function getVersionsByArgs(array $filter = []): array
    {
        return $this->versionManager->getVersions(
            array_merge($this->getVersionsFilterByArgs(), $filter),
        );
    }

    private function getVersionsFilterByArgs(): array
    {
        if ($this->getArg('--new')) {
            $status = VersionEnum::STATUS_NEW;
        } elseif ($this->getArg('--installed')) {
            $status = VersionEnum::STATUS_UNKNOWN;
        } elseif ($this->getArg('--unknown')) {
            $status = VersionEnum::STATUS_INSTALLED;
        } else {
            $status = '';
        }

        return [
            'status'   => $status,
            'tag'      => $this->getArg('--tag='),
            'search'   => $this->getArg('--search='),
            'modified' => $this->getArg('--modified'),
            'older'    => $this->getArg('--older'),
            'actual'   => $this->getArg('--actual'),
        ];
    }

    private function hasArguments(): bool
    {
        return !empty($this->arguments);
    }

    private function getArguments(): array
    {
        return $this->arguments;
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

    /**
     * @throws ConsoleException
     */
    private function authorizeAsAdmin(): void
    {
        $groupitem = CGroup::GetList('c_sort', 'asc', [
            'ADMIN'  => 'Y',
            'ACTIVE' => 'Y',
        ])->Fetch();

        if (empty($groupitem['ID'])) {
            throw new ConsoleException("Console authorize error: admin group not found");
        }

        $useritem = CUser::GetList('id', '', [
            'GROUPS_ID' => [$groupitem['ID']],
            'ACTIVE'    => 'Y',
        ], [
            'NAV_PARAMS' => ['nTopCount' => 1],
        ])->Fetch();

        if (empty($useritem)) {
            throw new ConsoleException("Console authorize error: admin user not found");
        }

        global $USER;
        $USER->Authorize($useritem['ID']);
    }

    /**
     * @throws ConsoleException
     */
    private function authorizeAsLogin($login): void
    {
        $dbres = CUser::GetByLogin($login);
        $useritem = $dbres->Fetch();
        if (empty($useritem['ID'])) {
            throw new ConsoleException("Console authorize error: user login not found");
        }

        global $USER;
        $USER->Authorize($useritem['ID']);
    }

}
