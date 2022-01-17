<?php

namespace Sprint\Migration;

use DirectoryIterator;
use Exception;
use Sprint\Migration\Builders\AgentBuilder;
use Sprint\Migration\Builders\BlankBuilder;
use Sprint\Migration\Builders\CacheCleanerBuilder;
use Sprint\Migration\Builders\EventBuilder;
use Sprint\Migration\Builders\FormBuilder;
use Sprint\Migration\Builders\HlblockBuilder;
use Sprint\Migration\Builders\HlblockElementsBuilder;
use Sprint\Migration\Builders\IblockBuilder;
use Sprint\Migration\Builders\IblockCategoryBuilder;
use Sprint\Migration\Builders\IblockElementsBuilder;
use Sprint\Migration\Builders\MarkerBuilder;
use Sprint\Migration\Builders\MedialibElementsBuilder;
use Sprint\Migration\Builders\OptionBuilder;
use Sprint\Migration\Builders\TransferBuilder;
use Sprint\Migration\Builders\UserGroupBuilder;
use Sprint\Migration\Builders\UserOptionsBuilder;
use Sprint\Migration\Builders\UserTypeEntitiesBuilder;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Schema\AgentSchema;
use Sprint\Migration\Schema\EventSchema;
use Sprint\Migration\Schema\GroupSchema;
use Sprint\Migration\Schema\HlblockSchema;
use Sprint\Migration\Schema\IblockSchema;
use Sprint\Migration\Schema\OptionSchema;
use Sprint\Migration\Schema\UserTypeEntitiesSchema;

class VersionConfig
{
    private $configCurrent = '';
    private $configList    = [];
    private $availablekeys = [
        'migration_table',
        'migration_extend_class',
        'stop_on_errors',
        'migration_dir',
        'migration_dir_absolute',
        'version_prefix',
        'version_filter',
        'version_builders',
        'version_schemas',
        'show_admin_interface',
        'version_name_template',
        'console_user',
        'console_auth_events_disable',
        'tracker_task_url',
    ];

    /**
     * VersionConfig constructor.
     *
     * @param string $configName
     * @param array  $configValues
     *
     * @throws MigrationException
     */
    public function __construct($configName = '', $configValues = [])
    {
        if (!is_string($configName) || !is_array($configValues)) {
            throw new MigrationException("Config params error");
        }

        if (!empty($configName) && !empty($configValues)) {
            $this->initializeByValues($configName, $configValues);
        } else {
            $this->initializeByName($configName);
        }
    }

    /**
     * @param $configName
     * @param $configValues
     *
     * @throws MigrationException
     */
    protected function initializeByValues($configName, $configValues)
    {
        $this->configList = [
            $configName => $this->prepare($configName, $configValues),
        ];

        $this->configCurrent = $configName;
    }

    /**
     * @param $configName
     *
     * @throws MigrationException
     */
    protected function initializeByName($configName)
    {
        $this->configList = $this->searchConfigs();

        if (!isset($this->configList[VersionEnum::CONFIG_DEFAULT])) {
            $this->configList[VersionEnum::CONFIG_DEFAULT] = $this->prepare(VersionEnum::CONFIG_DEFAULT);
        }

        if (!isset($this->configList[VersionEnum::CONFIG_ARCHIVE])) {
            $this->configList[VersionEnum::CONFIG_ARCHIVE] = $this->prepare(
                VersionEnum::CONFIG_ARCHIVE,
                [
                    'title'           => Locale::getMessage('CONFIG_archive'),
                    'migration_dir'   => $this->getSiblingDir('archive', true),
                    'migration_table' => 'sprint_migration_archive',
                ]
            );
        }

        uasort(
            $this->configList, function ($a, $b) {
            return ($a['sort'] >= $b['sort']);
        }
        );

        if (isset($this->configList[$configName])) {
            $this->configCurrent = $configName;
        } else {
            $this->configCurrent = VersionEnum::CONFIG_DEFAULT;
        }
    }

    /**
     * @param false $key
     *
     * @return mixed
     */
    public function getCurrent($key = false)
    {
        return ($key) ? $this->configList[$this->configCurrent][$key] : $this->configList[$this->configCurrent];
    }

    /**
     * @return array
     */
    public function getList()
    {
        return $this->configList;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->configList[$this->configCurrent]['name'];
    }

    /**
     * @throws MigrationException
     * @return array
     */
    protected function searchConfigs()
    {
        $result = [];
        $directory = new DirectoryIterator(Module::getPhpInterfaceDir());
        foreach ($directory as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $configName = $this->getConfigName($item->getFilename());
            if (!$configName) {
                continue;
            }

            /** @noinspection PhpIncludeInspection */
            $values = include $item->getPathname();
            if (!$this->isValuesValid($values)) {
                continue;
            }

            $result[$configName] = $this->prepare($configName, $values, $item->getPathname());
        }

        return $result;
    }

    /**
     * @param $fileName
     *
     * @return false|mixed
     */
    protected function getConfigName($fileName)
    {
        if (preg_match('/^migrations\.([a-z0-9_-]*)\.php$/i', $fileName, $matches)) {
            return $matches[1];
        }
        return false;
    }

    /**
     * @param $values
     *
     * @return bool
     */
    protected function isValuesValid($values)
    {
        foreach ($this->availablekeys as $key) {
            if (isset($values[$key])) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param       $configName
     * @param array $configValues
     * @param false $file
     *
     * @throws MigrationException
     * @return array
     */
    protected function prepare($configName, $configValues = [], $file = false)
    {
        $configValues = $this->prepareValues($configValues);

        if (!empty($configValues['title'])) {
            $title = sprintf('%s (%s)', $configValues['title'], $configName);
        } else {
            $title = sprintf('%s (%s)', Locale::getMessage('CFG_TITLE'), $configName);
        }

        if (!empty($configValues['schema_title'])) {
            $schemaTitle = sprintf('%s (%s)', $configValues['schema_title'], $configName);
        } else {
            $schemaTitle = sprintf('%s (%s)', Locale::getMessage('SCH_TITLE'), $configName);
        }

        if (isset($configValues['title'])) {
            unset($configValues['title']);
        }
        if (isset($configValues['schema_title'])) {
            unset($configValues['schema_title']);
        }

        return [
            'name'         => $configName,
            'sort'         => $this->getSort($configName),
            'title'        => $title,
            'schema_title' => $schemaTitle,
            'file'         => $file,
            'values'       => $configValues,
        ];
    }

    /**
     * @param array $values
     *
     * @throws MigrationException
     * @return array|mixed
     */
    protected function prepareValues($values = [])
    {
        if (empty($values['migration_extend_class'])) {
            $values['migration_extend_class'] = 'Version';
        }

        if (empty($values['migration_table'])) {
            $values['migration_table'] = 'sprint_migration_versions';
        }

        if (empty($values['migration_dir'])) {
            $values['migration_dir'] = Module::getPhpInterfaceDir() . '/migrations';
        } elseif (empty($values['migration_dir_absolute'])) {
            $values['migration_dir'] = Module::getDocRoot() . $values['migration_dir'];
        }

        if (!is_dir($values['migration_dir'])) {
            Module::createDir($values['migration_dir']);
            $values['migration_dir'] = realpath($values['migration_dir']);
        } else {
            $values['migration_dir'] = realpath($values['migration_dir']);
        }

        if (empty($values['version_prefix'])) {
            $values['version_prefix'] = 'Version';
        }

        if (!isset($values['version_filter']) || !is_array($values['version_filter'])) {
            $values['version_filter'] = [];
        }

        if (isset($values['show_admin_interface'])) {
            $values['show_admin_interface'] = (bool)$values['show_admin_interface'];
        } else {
            $values['show_admin_interface'] = true;
        }

        if (isset($values['stop_on_errors'])) {
            $values['stop_on_errors'] = (bool)$values['stop_on_errors'];
        } else {
            $values['stop_on_errors'] = false;
        }

        if (isset($values['console_auth_events_disable'])) {
            $values['console_auth_events_disable'] = (bool)$values['console_auth_events_disable'];
        } else {
            $values['console_auth_events_disable'] = true;
        }

        $cond1 = isset($values['console_user']);
        $cond2 = ($cond1 && $values['console_user'] === false);
        $cond3 = ($cond1 && strpos($values['console_user'], 'login:') === 0);

        $values['console_user'] = ($cond2 || $cond3) ? $values['console_user'] : 'admin';

        if (empty($values['version_builders']) || !is_array($values['version_builders'])) {
            $values['version_builders'] = VersionConfig::getDefaultBuilders();
        }

        if (!empty($values['version_schemas']) || !is_array($values['version_schemas'])) {
            $values['version_schemas'] = VersionConfig::getDefaultSchemas();
        }

        if (empty($values['tracker_task_url'])) {
            $values['tracker_task_url'] = '';
        }

        if (empty($values['version_name_template'])) {
            $values['version_name_template'] = '#NAME##TIMESTAMP#';
        }

        if (
            (strpos($values['version_name_template'], '#TIMESTAMP#') === false)
            || (strpos($values['version_name_template'], '#NAME#') === false)
        ) {
            throw new MigrationException("Config version_name_template format error");
        }

        ksort($values);
        return $values;
    }

    /**
     * @param array $values
     *
     * @return array|mixed
     */
    public function humanValues($values = [])
    {
        foreach ($values as $key => $val) {
            if ($val === true || $val === false) {
                $val = ($val) ? 'yes' : 'no';
                $val = Locale::getMessage('CONFIG_' . $val);
            } elseif (is_array($val)) {
                $fres = [];
                foreach ($val as $fkey => $fval) {
                    $fres[] = '[' . $fkey . '] => ' . $fval;
                }
                $val = implode(PHP_EOL, $fres);
            }
            $values[$key] = (string)$val;
        }
        return $values;
    }

    /**
     * @param        $name
     * @param string $default
     *
     * @return bool|mixed|string
     */
    public function getVal($name, $default = '')
    {
        $values = $this->configList[$this->configCurrent]['values'];

        if (isset($values[$name])) {
            if (is_bool($values[$name])) {
                return $values[$name];
            } elseif (!empty($values[$name])) {
                return $values[$name];
            }
        }

        return $default;
    }

    /**
     * @param       $configName
     * @param array $configValues
     *
     * @return bool
     */
    public function createConfig($configName, $configValues = [])
    {
        $fileName = 'migrations.' . $configName . '.php';
        if (!$this->getConfigName($fileName)) {
            return false;
        }

        $configPath = Module::getPhpInterfaceDir() . '/' . $fileName;
        if (is_file($configPath)) {
            return false;
        }

        if (isset($this->configList[$configName])) {
            $curValues = $this->configList[$configName]['values'];
            $configDefaults = [
                'migration_dir'   => Module::getRelativeDir($curValues['migration_dir']),
                'migration_table' => $curValues['migration_table'],
            ];
        } else {
            $configDefaults = [
                'migration_dir'   => $this->getSiblingDir($configName, true),
                'migration_table' => 'sprint_migration_' . $configName,
            ];
        }

        $configValues = array_merge($configDefaults, $configValues);

        file_put_contents($configPath, '<?php return ' . var_export($configValues, 1) . ';');
        return is_file($configPath);
    }

    /**
     * @param $configName
     *
     * @throws Exception
     * @return bool
     */
    public function deleteConfig($configName)
    {
        $fileName = 'migrations.' . $configName . '.php';
        if (!$this->getConfigName($fileName)) {
            return false;
        }

        if (!isset($this->configList[$configName])) {
            return false;
        }

        $configFile = $this->configList[$configName]['file'];

        $vmFrom = new VersionManager($configName);
        $vmFrom->clean();

        if (!empty($configFile) && is_file($configFile)) {
            unlink($configFile);
        }

        return true;
    }

    /**
     * @param        $dirname
     * @param false  $relative
     * @param string $configName
     *
     * @return false|string|string[]
     */
    public function getSiblingDir($dirname, $relative = false, $configName = VersionEnum::CONFIG_DEFAULT)
    {
        $def = $this->configList[$configName];
        $dir = rtrim($def['values']['migration_dir'], '/');
        $dir = $dir . '.' . trim($dirname, '/') . '/';

        return ($relative) ? Module::getRelativeDir($dir) : $dir;
    }

    /**
     * @param $configName
     *
     * @return int
     */
    protected function getSort($configName)
    {
        if ($configName == VersionEnum::CONFIG_ARCHIVE) {
            return 110;
        } elseif ($configName == VersionEnum::CONFIG_DEFAULT) {
            return 100;
        } else {
            return 500;
        }
    }

    /**
     * Метод должен быть публичным для работы со сторонним кодом
     * @return string[]
     */
    public static function getDefaultBuilders()
    {
        return [
            'UserGroupBuilder'        => UserGroupBuilder::class,
            'IblockBuilder'           => IblockBuilder::class,
            'HlblockBuilder'          => HlblockBuilder::class,
            'IblockElementsBuilder'   => IblockElementsBuilder::class,
            'IblockCategoryBuilder'   => IblockCategoryBuilder::class,
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

    /**
     * Метод должен быть публичным для работы со сторонним кодом
     * @return string[]
     */
    public static function getDefaultSchemas()
    {
        return [
            'IblockSchema'           => IblockSchema::class,
            'HlblockSchema'          => HlblockSchema::class,
            'UserTypeEntitiesSchema' => UserTypeEntitiesSchema::class,
            'AgentSchema'            => AgentSchema::class,
            'GroupSchema'            => GroupSchema::class,
            'OptionSchema'           => OptionSchema::class,
            'EventSchema'            => EventSchema::class,
        ];
    }
}



