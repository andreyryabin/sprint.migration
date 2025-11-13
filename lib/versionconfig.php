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
use Sprint\Migration\Builders\IblockPropertyBuilder;
use Sprint\Migration\Builders\MarkerBuilder;
use Sprint\Migration\Builders\MedialibElementsBuilder;
use Sprint\Migration\Builders\OptionBuilder;
use Sprint\Migration\Builders\TransferBuilder;
use Sprint\Migration\Builders\UserGroupBuilder;
use Sprint\Migration\Builders\UserOptionsBuilder;
use Sprint\Migration\Builders\UserTypeEntitiesBuilder;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;

class VersionConfig
{
    private string $name;
    private array  $values;
    private string $path;

    /**
     * @throws MigrationException
     */
    public function __construct(string $name, array $values, string $path = '')
    {
        $this->name = $name;
        $this->path = $path;
        $this->values = $this->makeValues($values);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTitle(): string
    {
        return sprintf('%s (%s)', $this->getVal('title'), $this->name);
    }

    public function getValues(): array
    {
        return $this->values;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSort(): int
    {
        return ($this->getName() == VersionEnum::CONFIG_DEFAULT) ? 100 : 200;
    }

    public function getVal(string $name, mixed $default = ''): mixed
    {
        return $this->values[$name] ?? $default;
    }

    public function humanValues(): array
    {
        $human = [];

        foreach ($this->getValues() as $key => $val) {
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
            $human[$key] = (string)$val;
        }
        return $human;
    }

    public function getVersionExchangeDir(string $versionName): string
    {
        $dir = $this->getVal('exchange_dir');
        return $dir . '/' . $versionName . '_files/';
    }

    public function getVersionFile(string $versionName): string
    {
        $dir = $this->getVal('migration_dir');
        return $dir . '/' . $versionName . '.php';
    }

    public function getWebDir(): string
    {
        $dir = $this->getVal('migration_dir');
        if (str_starts_with($dir, Module::getDocRoot())) {
            return substr($dir, strlen(Module::getDocRoot()));
        }
        return '';
    }

    /**
     * @throws MigrationException
     */
    protected function makeValues(array $values = []): array
    {
        if (empty($values['title'])) {
            $values['title'] = Locale::getMessage('CFG_TITLE');
        }

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
        }
        $values['migration_dir'] = realpath($values['migration_dir']);

        if (empty($values['exchange_dir'])) {
            $values['exchange_dir'] = $values['migration_dir'];
        } else {
            $values['exchange_dir'] = rtrim($values['exchange_dir'], DIRECTORY_SEPARATOR);
            if (empty($values['exchange_dir_absolute'])) {
                $values['exchange_dir'] = Module::getDocRoot() . $values['exchange_dir'];
            }
        }

        if (empty($values['version_prefix'])) {
            $values['version_prefix'] = 'Version';
        }

        if (isset($values['show_admin_interface'])) {
            $values['show_admin_interface'] = (bool)$values['show_admin_interface'];
        } else {
            $values['show_admin_interface'] = true;
        }

        if (isset($values['console_auth_events_disable'])) {
            $values['console_auth_events_disable'] = (bool)$values['console_auth_events_disable'];
        } else {
            $values['console_auth_events_disable'] = true;
        }

        $cond1 = isset($values['console_user']);
        $cond2 = ($cond1 && $values['console_user'] === false);
        $cond3 = ($cond1 && str_starts_with($values['console_user'], 'login:'));

        $values['console_user'] = ($cond2 || $cond3) ? $values['console_user'] : 'admin';

        if (!isset($values['version_builders']) || !is_array($values['version_builders'])) {
            $values['version_builders'] = VersionConfig::getDefaultBuilders();
        }

        if (empty($values['tracker_task_url'])) {
            $values['tracker_task_url'] = '';
        }

        if (empty($values['version_name_template'])) {
            $values['version_name_template'] = '#NAME##TIMESTAMP#';
        }

        if (
            (!str_contains($values['version_name_template'], '#TIMESTAMP#'))
            || (!str_contains($values['version_name_template'], '#NAME#'))
        ) {
            throw new MigrationException("Config version_name_template format error");
        }

        if (empty($values['version_timestamp_pattern'])) {
            $values['version_timestamp_pattern'] = '/20\d{12}/';
        }

        if (empty($values['version_timestamp_format'])) {
            $values['version_timestamp_format'] = 'YmdHis';
        }

        if (empty($values['migration_hash_algo'])) {
            $values['migration_hash_algo'] = 'md5';
        }

        ksort($values);
        return $values;
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
            'IblockPropertyBuilder' => IblockPropertyBuilder::class,
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
