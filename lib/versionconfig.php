<?php

namespace Sprint\Migration;

use Sprint\Migration\Builders\AgentBuilder;
use Sprint\Migration\Builders\BlankBuilder;
use Sprint\Migration\Builders\CacheCleanerBuilder;
use Sprint\Migration\Builders\EventBuilder;
use Sprint\Migration\Builders\FormBuilder;
use Sprint\Migration\Builders\HlblockBuilder;
use Sprint\Migration\Builders\HlblockElementsBuilder;
use Sprint\Migration\Builders\IblockBuilder;
use Sprint\Migration\Builders\IblockCategoryBuilder;
use Sprint\Migration\Builders\IblockDeleteBuilder;
use Sprint\Migration\Builders\IblockElementsBuilder;
use Sprint\Migration\Builders\MarkerBuilder;
use Sprint\Migration\Builders\MedialibElementsBuilder;
use Sprint\Migration\Builders\OptionBuilder;
use Sprint\Migration\Builders\TransferBuilder;
use Sprint\Migration\Builders\UserGroupBuilder;
use Sprint\Migration\Builders\UserOptionsBuilder;
use Sprint\Migration\Builders\UserTypeEntitiesBuilder;
use Sprint\Migration\Config\Config;
use Sprint\Migration\Config\ConfigCollection;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;

class VersionConfig
{
    private string           $configCurrent;
    private ConfigCollection $configList;

    /**
     * @throws MigrationException
     */
    public function __construct(string $configName = '', array $configValues = [])
    {
        $this->configList = new ConfigCollection();

        if (!empty($configName) && !empty($configValues)) {
            $this->configList->add($configName, $configValues, '');
            $this->configCurrent = $configName;
        } else {
            $this->configCurrent = $configName ?: VersionEnum::CONFIG_DEFAULT;
            $this->configList->addFromEventHandlers()->addDefault($this->configCurrent)->resort();
        }
    }

    /**
     * @throws MigrationException
     */
    public function getCurrent(): Config
    {
        return $this->configList->get($this->configCurrent);
    }

    /**
     * @return array|Config[]
     */
    public function getConfigList(): array
    {
        return $this->configList->getIterator();
    }

    public function getVersionExchangeDir(string $versionName): string
    {
        $dir = $this->getCurrent()->getVal('exchange_dir');
        return $dir . '/' . $versionName . '_files/';
    }

    public function createConfig(string $configName): bool
    {
        $fileName = 'migrations.' . $configName . '.php';
        if (!$this->getConfigName($fileName)) {
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
        return is_file($configPath);
    }

    /**
     * @throws MigrationException
     */
    public function deleteConfig(string $configName): bool
    {
        $fileName = 'migrations.' . $configName . '.php';
        if (!$this->getConfigName($fileName)) {
            return false;
        }

        if (!isset($this->configList[$configName])) {
            return false;
        }

        $configFile = $this->configList[$configName]['file'];

        $vmFrom = new VersionManager(
            new VersionConfig($configName)
        );
        $vmFrom->clean();

        if (!empty($configFile) && is_file($configFile)) {
            unlink($configFile);
        }

        return true;
    }

    /**
     * Метод должен быть публичным для работы со сторонним кодом
     *
     * @return string[]
     */
    public static function getDefaultBuilders(): array
    {
        return [
            'UserGroupBuilder'        => UserGroupBuilder::class,
            'IblockBuilder'           => IblockBuilder::class,
            'IblockCategoryBuilder'   => IblockCategoryBuilder::class,
            'IblockElementsBuilder'   => IblockElementsBuilder::class,
            'IblockDeleteBuilder'     => IblockDeleteBuilder::class,
            'HlblockBuilder'          => HlblockBuilder::class,
            'HlblockElementsBuilder'  => HlblockElementsBuilder::class,
            'UserTypeEntitiesBuilder' => UserTypeEntitiesBuilder::class,
            'AgentBuilder'            => AgentBuilder::class,
            'OptionBuilder'           => OptionBuilder::class,
            'FormBuilder'             => FormBuilder::class,
            'EventBuilder'            => EventBuilder::class,
            'UserOptionsBuilder'      => UserOptionsBuilder::class,
            'MedialibElementsBuilder' => MedialibElementsBuilder::class,
            'BlankBuilder'            => BlankBuilder::class,
            'CacheCleanerBuilder'     => CacheCleanerBuilder::class,
            'MarkerBuilder'           => MarkerBuilder::class,
            'TransferBuilder'         => TransferBuilder::class,
        ];
    }
}



