<?php

namespace Sprint\Migration\Config;

use DirectoryIterator;
use Sprint\Migration\Enum\EventsEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Module;

class ConfigCollection
{
    private array $collection = [];

    /**
     * @throws MigrationException
     */
    public function add(string $name, array $values, string $path): ConfigCollection
    {
        $config = new Config($name, $values, $path);

        if (isset($this->collection[$name])) {
            throw new MigrationException('Config already exists: ' . $name);
        }

        $this->collection[$name] = $config;

        return $this;
    }

    /**
     * @throws MigrationException
     */
    public function addDefault(string $name): ConfigCollection
    {
        if (!isset($this->collection[$name])) {
            $this->add($name, [], '');
        }

        return $this;
    }

    /**
     * @throws MigrationException
     */
    public function get(string $name): Config
    {
        if (isset($this->collection[$name])) {
            return $this->collection[$name];
        }

        throw new MigrationException('Config not found: ' . $name);
    }

    /**
     * @return array|Config[]
     */
    public function getIterator(): array
    {
        return $this->collection;
    }

    public function resort(): ConfigCollection
    {
        uasort($this->collection, fn(Config $a, Config $b) => ($a->getSort() <=> $b->getSort()));

        return $this;
    }

    /**
     * @throws MigrationException
     */
    public function addFromEventHandlers(): ConfigCollection
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
    public function addFromDirectory(string $directory): ConfigCollection
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

        return $this;
    }

    private function makeConfigName(string $fileName): string
    {
        if (preg_match('/^migrations\.([a-z0-9_-]*)\.php$/i', $fileName, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
