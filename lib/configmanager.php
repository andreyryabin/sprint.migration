<?php

namespace Sprint\Migration;

use DirectoryIterator;
use Sprint\Migration\Enum\EventsEnum;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;

class ConfigManager
{
    private array                 $collection = [];
    private static ?ConfigManager $instance   = null;

    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    /**
     * @throws MigrationException
     */
    public function __construct()
    {
        $this->addFromEventHandlers()
             ->addDefault()
             ->resort();
    }

    /**
     * @throws MigrationException
     */
    private function add(string $name, array $values, string $path): void
    {
        $config = new VersionConfig($name, $values, $path);

        if (isset($this->collection[$name])) {
            throw new MigrationException('Config already exists: ' . $name);
        }

        $this->collection[$name] = $config;
    }

    /**
     * @throws MigrationException
     */
    private function addDefault(): static
    {
        $name = VersionEnum::CONFIG_DEFAULT;

        if (!isset($this->collection[$name])) {
            $this->add($name, [], '');
        }

        return $this;
    }

    /**
     * @throws MigrationException
     */
    public function get(string $name): VersionConfig
    {
        if (isset($this->collection[$name])) {
            return $this->collection[$name];
        }

        throw new MigrationException('Config not found: ' . $name);
    }

    /**
     * @return array|VersionConfig[]
     */
    public function getList(): array
    {
        return $this->collection;
    }

    public function getListAssoc(): array
    {
        $map = [];
        foreach ($this->getList() as $config) {
            $map[$config->getName()] = $config->getTitle();
        }
        return $map;
    }

    private function resort(): void
    {
        uasort(
            $this->collection,
            fn(VersionConfig $a, VersionConfig $b) => ($a->getSort() <=> $b->getSort())
        );
    }

    /**
     * @throws MigrationException
     */
    private function addFromEventHandlers(): static
    {
        $events = GetModuleEvents(
            Module::ID,
            EventsEnum::ON_SEARCH_CONFIG_FILES,
            true
        );

        foreach ($events as $aEvent) {
            $customPath = (string)ExecuteModuleEventEx($aEvent);
            $this->addFromDirectory($customPath);
        }

        return $this;
    }

    /**
     * @throws MigrationException
     */
    private function addFromDirectory(string $directory): void
    {
        $directory = new DirectoryIterator($directory);
        foreach ($directory as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $configName = $this->makeConfigName($item->getFilename());
            if (!$configName) {
                continue;
            }

            $values = include $item->getPathname();
            if (!is_array($values)) {
                continue;
            }

            $this->add($configName, $values, $item->getPathname());
        }
    }

    public function createConfig(string $configName): bool
    {
        $fileName = 'migrations.' . $configName . '.php';
        if (!$this->makeConfigName($fileName)) {
            return false;
        }

        $configPath = Module::getPhpInterfaceDir() . '/' . $fileName;
        if (is_file($configPath)) {
            return false;
        }

        $configValues = [
            'migration_dir'   => Module::getPhpInterfaceDir(false) . '/migrations.' . $configName,
            'migration_table' => 'sprint_migration_' . $configName,
        ];

        file_put_contents($configPath, '<?php return ' . var_export($configValues, 1) . ';');

        if (is_file($configPath)) {
            $this->add($configName, $configValues, $configPath);
            return true;
        }

        return false;
    }

    /**
     * @throws MigrationException
     */
    public function deleteConfig(string $configName): bool
    {
        $fileName = 'migrations.' . $configName . '.php';
        if (!$this->makeConfigName($fileName)) {
            return false;
        }

        $config = $this->get($configName);

        $vmFrom = new VersionManager($config);

        $vmFrom->clean();

        if ($config->getPath() && is_file($config->getPath())) {
            unlink($config->getPath());
        }

        unset($this->collection[$configName]);

        return true;
    }

    private function makeConfigName(string $fileName): string
    {
        if (preg_match('/^migrations\.([a-z0-9_-]*)\.php$/i', $fileName, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
