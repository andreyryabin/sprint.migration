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
    private string $configName;

    use CurrentUserTrait;

    /**
     * @throws MigrationException
     */
    public function __construct($args)
    {
        $this->initializeArgs($args);

        $this->versionConfig = ConfigManager::getInstance()->get($this->configName);

        $this->output = new ConsoleOutput();

        $this->versionManager = new VersionManager(
            $this->versionConfig,
            $this->output,
        );

        $this->builderManager = new BuilderManager(
            $this->versionConfig,
            $this->output,
        );
    }


    /**
     * @throws ConsoleException
     */
    public function executeConsoleCommand(): void
    {
        $this->disableAuthHandlersIfNeed();

        $userlogin = $this->versionConfig->getVal('console_user');
        if ($userlogin == 'admin') {
            $this->authorizeAsAdmin();
        } elseif (str_starts_with($userlogin, 'login:')) {
            $userlogin = substr($userlogin, 6);
            $this->authorizeAsLogin($userlogin);
        }

        if (empty($this->command)) {
            $this->commandInfo();
            return;
        }

        if (method_exists($this, $this->command)) {
            call_user_func([$this, $this->command]);
            return;
        }

        throw new ConsoleException("Command $this->command not found, see help");
    }

    /**
     * @publicApi
     * @throws ConsoleException
     */
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
                $this->output->print('  [green]%s[/] *', $configItem->getTitle());
            } else {
                $this->output->print('  %s', $configItem->getTitle());
            }
        }
        $this->output->print('');
        $this->output->print(Locale::getMessage('COMMAND_CONFIG'));
        $this->output->print('  php %s config', $this->script);
        $this->output->print('');
        $this->output->print(Locale::getMessage('COMMAND_RUN'));
        $this->output->print('  php %s <command> [<args>]', $this->script);
        $this->output->print('');
        $this->output->print(Locale::getMessage('COMMAND_HELP') . ':');
        $this->output->print('  php %s help', $this->script);
        $this->output->print('');
    }

    /**
     * @publicApi
     * @throws ConsoleException
     */
    public function commandConfig(): void
    {
        $this->output->print($this->versionConfig->getTitle());

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
     * @throws MigrationException
     * @throws ConsoleException
     */
    public function commandMark(): void
    {
        $status = $this->getArg('--as=');
        $tag = $this->getArg('--add-tag=');

        if (empty($status) || empty($this->arguments)) {
            throw new ConsoleException("Invalid arguments, see help");
        }

        foreach ($this->arguments as $argument) {
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
     * @throws ConsoleException
     */
    public function commandDelete(): void
    {
        if (empty($this->arguments)) {
            throw new ConsoleException("Invalid arguments, see help");
        }

        foreach ($this->arguments as $argument) {
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

        if (empty($tag) || empty($this->arguments)) {
            throw new ConsoleException("Invalid arguments, see help");
        }

        foreach ($this->arguments as $argument) {
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
        $this->executeVersionsByArgs(VersionEnum::ACTION_UP);
    }

    /**
     * @publicApi
     * @throws MigrationException
     */
    public function commandDown(): void
    {
        $this->executeVersionsByArgs(VersionEnum::ACTION_DOWN);
    }

    /**
     * @publicApi
     * @throws MigrationException
     */
    public function commandMigrate(): void
    {
        $action = $this->getArg('--down') ? VersionEnum::ACTION_DOWN : VersionEnum::ACTION_UP;

        $this->executeVersionsByArgs($action);
    }

    /**
     * @publicApi
     * @throws MigrationException
     */
    public function commandRedo(): void
    {
        $tag = $this->getArg('--add-tag=');

        foreach ($this->arguments as $versionName) {
            if (!$this->versionManager->executeVersion($versionName, VersionEnum::ACTION_DOWN, $tag)
                || !$this->versionManager->executeVersion($versionName, VersionEnum::ACTION_UP, $tag)) {
                return;
            }
        }
    }

    /**
     * @publicApi
     * @throws ConsoleException
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
     * @throws ConsoleException
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
     * @publicApi
     * @throws ConsoleException
     */
    public function commandBuilders(): void
    {
        $builders = $this->builderManager->getBuilders();

        $groups = [];
        foreach ($builders as $builder) {
            $name = $builder->getName();
            $title = $builder->getTitle();
            $group = $builder->getGroup();

            if (!isset($groups[$group])) {
                $groups[$group] = [];
            }

            $groups[$group][] = ['name' => $name, 'title' => $title];
        }

        foreach ($groups as $group => $items) {
            $this->output->print("[blue]%s[/]", $group);
            foreach ($items as $item) {
                $this->output->print(" > (%s) - %s", $item['name'], $item['title']);
            }
        }
    }

    /**
     * @publicApi
     * @throws BuilderException
     * @throws ConsoleException
     */
    public function commandAdd(): void
    {
        $this->executeBuilderByArgs();
    }

    /**
     * @publicApi
     * @throws BuilderException
     * @throws ConsoleException
     */
    public function commandRun(): void
    {
        $this->executeBuilderByArgs();
    }

    /**
     * @publicApi
     * @throws MigrationException
     * @throws ConsoleException
     */
    public function commandLs(): void
    {
        $this->commandList();
    }

    /**
     * @throws MigrationException
     * @throws ConsoleException
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

    /**
     * @throws BuilderException
     * @throws ConsoleException
     */
    private function executeBuilderByArgs(): void
    {
        $name = $this->getArg(0, 'BlankBuilder');
        $defaultValues = $this->getBuilderParamsByArgs();

        $builder = $this->builderManager->createBuilder($name);
        $this->output->print("%s [%s]", $builder->getTitle(), $builder->getName());

        do {
            foreach ($builder->getUnbindFields() as $field) {
                $raw = $defaultValues[$field->getCode()] ?? $field->readValueFromConsole($this->output);
                $builder->setFieldValue($field->getCode(), $field->setValueFromConsole($raw));
            }
            $result = $this->builderManager->startBuilder($builder);
        } while ($result->needRestart() || $result->needRebuild());
    }

    /**
     * @throws MigrationException
     */
    private function executeVersionsByArgs($action): void
    {
        $tag = $this->getArg('--add-tag=');

        if (empty($this->arguments)) {
            $this->versionManager->executeAll($this->getVersionsFilterByArgs(), $action, $tag);
            return;
        }

        foreach ($this->arguments as $versionName) {
            if (!$this->versionManager->executeVersion($versionName, $action, $tag)) {
                return;
            }
        }
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

    private function getBuilderParamsByArgs(): array
    {
        // '--prefix=' -> 'prefix'
        $data = [];
        foreach ($this->argoptions as $key => $val) {
            $name = rtrim(substr($key, 2), '=');
            $data[$name] = $val;
        }
        return $data;
    }

    private function initializeArgs(array $args): void
    {
        $this->script = array_shift($args) ?? '';

        foreach ($args as $arg) {
            if (str_starts_with($arg, '--')) {
                [$name, $val] = explode('=', $arg, 2);
                if ($val !== null) {
                    $this->argoptions[$name . '='] = $val;
                } else {
                    $this->argoptions[$name] = true;
                }
            } elseif (!str_starts_with($arg, '-')) {
                $this->arguments[] = $arg;
            }
        }

        $this->configName = VersionEnum::CONFIG_DEFAULT;
        if (isset($this->argoptions['--config='])) {
            $this->configName = $this->argoptions['--config='];
            unset($this->argoptions['--config=']);
        }

        if (isset($this->arguments[0])) {
            $this->command = array_shift($this->arguments);
            $this->command = 'command' . ucfirst($this->command);
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
