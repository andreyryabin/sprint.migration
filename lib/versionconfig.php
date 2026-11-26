<?php

namespace Sprint\Migration;

use Sprint\Migration\Builders\AgentBuilder;
use Sprint\Migration\Builders\BlankBuilder;
use Sprint\Migration\Builders\BlogBuilder;
use Sprint\Migration\Builders\BlogPostBuilder;
use Sprint\Migration\Builders\BlogUserTypeEntitiesBuilder;
use Sprint\Migration\Builders\CacheCleanerBuilder;
use Sprint\Migration\Builders\EventBuilder;
use Sprint\Migration\Builders\FormBuilder;
use Sprint\Migration\Builders\ForumBuilder;
use Sprint\Migration\Builders\HlblockBuilder;
use Sprint\Migration\Builders\HlblockElementsBuilder;
use Sprint\Migration\Builders\IblockBuilder;
use Sprint\Migration\Builders\IblockCategoryBuilder;
use Sprint\Migration\Builders\IblockDeleteBuilder;
use Sprint\Migration\Builders\IblockElementsBuilder;
use Sprint\Migration\Builders\IblockPropertyBuilder;
use Sprint\Migration\Builders\IblockPropertyDeleteBuilder;
use Sprint\Migration\Builders\LanguageBuilder;
use Sprint\Migration\Builders\MedialibElementsBuilder;
use Sprint\Migration\Builders\OptionBuilder;
use Sprint\Migration\Builders\OrderPropertiesBuilder;
use Sprint\Migration\Builders\SaleDiscountBuilder;
use Sprint\Migration\Builders\SubscribeBuilder;
use Sprint\Migration\Builders\UserGroupBuilder;
use Sprint\Migration\Builders\UserOptionsBuilder;
use Sprint\Migration\Builders\UserTypeEntitiesBuilder;
use Sprint\Migration\Builders\VoteBuilder;
use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;

class VersionConfig
{
    private string $name;
    private array $values;
    private string $path;
    private array $checkedVersionNames = [];

    /**
     * @throws MigrationException
     */
    public function __construct(string $name, array $values, string $path = '')
    {
        $this->name = $name;
        $this->path = $path;
        $this->values = $this->makeValues($values);
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

        if (empty($values['version_prefix']) || !$this->isVersionPrefix($values['version_prefix'])) {
            $values['version_prefix'] = 'Version';
        }

        if (isset($values['show_admin_interface'])) {
            $values['show_admin_interface'] = (bool) $values['show_admin_interface'];
        } else {
            $values['show_admin_interface'] = true;
        }

        if (isset($values['console_auth_events_disable'])) {
            $values['console_auth_events_disable'] = (bool) $values['console_auth_events_disable'];
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

        if (empty($values['version_name_template'])) {
            $values['version_name_template'] = '#NAME##TIMESTAMP#';
        }

        if (
            (!str_contains($values['version_name_template'], '#TIMESTAMP#'))
            || (!str_contains($values['version_name_template'], '#NAME#'))
        ) {
            throw new MigrationException("Config version_name_template format error");
        }

        if (empty($values['version_timestamp_format'])) {
            $values['version_timestamp_format'] = 'YmdHis';
        }

        $values['version_timestamp_pattern'] = $this->maskToRegex(
            $values['version_timestamp_format'],
        );

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
            'UserGroupBuilder'            => UserGroupBuilder::class,
            'LanguageBuilder'             => LanguageBuilder::class,
            'IblockBuilder'               => IblockBuilder::class,
            'IblockPropertyBuilder'       => IblockPropertyBuilder::class,
            'IblockPropertyDeleteBuilder' => IblockPropertyDeleteBuilder::class,
            'IblockCategoryBuilder'       => IblockCategoryBuilder::class,
            'IblockElementsBuilder'       => IblockElementsBuilder::class,
            'IblockDeleteBuilder'         => IblockDeleteBuilder::class,
            'HlblockBuilder'              => HlblockBuilder::class,
            'HlblockElementsBuilder'      => HlblockElementsBuilder::class,
            'UserTypeEntitiesBuilder'     => UserTypeEntitiesBuilder::class,
            'AgentBuilder'                => AgentBuilder::class,
            'BlogBuilder'                 => BlogBuilder::class,
            'BlogPostBuilder'             => BlogPostBuilder::class,
            'BlogUserTypeEntitiesBuilder' => BlogUserTypeEntitiesBuilder::class,
            'OptionBuilder'               => OptionBuilder::class,
            'FormBuilder'                 => FormBuilder::class,
            'ForumBuilder'                => ForumBuilder::class,
            'VoteBuilder'                 => VoteBuilder::class,
            'EventBuilder'                => EventBuilder::class,
            'SubscribeBuilder'            => SubscribeBuilder::class,
            'UserOptionsBuilder'          => UserOptionsBuilder::class,
            'OrderPropertiesBuilder'      => OrderPropertiesBuilder::class,
            'SaleDiscountBuilder'         => SaleDiscountBuilder::class,
            'MedialibElementsBuilder'     => MedialibElementsBuilder::class,
            'BlankBuilder'                => BlankBuilder::class,
            'CacheCleanerBuilder'         => CacheCleanerBuilder::class,
        ];
    }

    /**
     * @throws MigrationException
     */
    private function maskToRegex(string $mask): string
    {
        $supportedFormats = [
            'YmdHis'    => '/20\d{12}/',
            'Ymd_His'   => '/20\d{6}_\d{6}/',
            'Y_m_d_His' => '/20\d{2}_\d{2}_\d{2}_\d{6}/',
            'dmYHis'    => '/\d{2}\d{2}20\d{2}\d{6}/',
            'dmY_His'   => '/\d{2}\d{2}20\d{2}_\d{6}/',
            'd_m_Y_His' => '/\d{2}_\d{2}_20\d{2}_\d{6}/',
        ];

        if (isset($supportedFormats[$mask])) {
            return $supportedFormats[$mask];
        }

        throw new MigrationException("Config version_timestamp_format has unsupported format");
    }

    public function getTitle(): string
    {
        return sprintf('%s (%s)', $this->getVal('title'), $this->name);
    }

    public function getVal(string $name, mixed $default = ''): mixed
    {
        return $this->values[$name] ?? $default;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getSort(): int
    {
        $sort = (int) $this->getVal('sort', 200);

        return ($this->getName() == VersionEnum::CONFIG_DEFAULT) ? 100 : $sort;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function humanValues(): array
    {
        $human = [];

        foreach ($this->getValues() as $key => $val) {
            if ($val === true || $val === false) {
                $val = Locale::getMessage(($val) ? 'LABEL_YES' : 'LABEL_NO');
            } elseif (is_array($val)) {
                $fres = [];
                foreach ($val as $fkey => $fval) {
                    $fres[] = '[' . $fkey . '] => ' . $fval;
                }
                $val = implode(PHP_EOL, $fres);
            } elseif (is_object($val)) {
                $val = get_class($val);
            }
            $human[$key] = (string) $val;
        }
        return $human;
    }

    public function getValues(): array
    {
        return $this->values;
    }

    /**
     * @throws MigrationException
     */
    public function getVersionExchangeDir(string $versionName): string
    {
        $this->checkVersionName($versionName);

        return $this->getVal('exchange_dir') . '/' . $versionName . '_files/';
    }

    public function isVersionName(string $versionName): bool
    {
        try {
            $this->checkVersionName($versionName);
            return true;
        } catch (MigrationException) {
            return false;
        }
    }

    public function isVersionStatus(string $status): bool
    {
        try {
            $this->checkVersionStatus($status);
            return true;
        } catch (MigrationException) {
            return false;
        }
    }

    /**
     * @throws MigrationException
     */
    public function checkVersionStatus(string $status): void
    {
        $all = [
            VersionEnum::STATUS_NEW,
            VersionEnum::STATUS_INSTALLED,
            VersionEnum::STATUS_UNKNOWN,
        ];

        if (!in_array($status, $all)) {
            throw new MigrationException('Invalid version status');
        }
    }


    /**
     * Имя версии долджно быть валидным названием php-класса
     * первый символ: буква или подчёркивание,
     * далее любые буквы, цифры, подчёркивания, без разделителей пути
     * @throws MigrationException
     */
    public function checkVersionName(string $versionName): void
    {
        if (isset($this->checkedVersionNames[$versionName])) {
            return;
        }

        if (!$versionName || !$this->isVersionPrefix($versionName)) {
            throw new MigrationException('Invalid version name');
        }

        $pattern = $this->getVal('version_timestamp_pattern');
        $matches = [];
        if (!preg_match($pattern, $versionName, $matches)) {
            throw new MigrationException('Invalid version timestamp');
        }

        $this->checkedVersionNames[$versionName] = end($matches);
    }

    public function isVersionPrefix(string $prefix): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $prefix);
    }

    /**
     * @throws MigrationException
     */
    public function getVersionTimestamp(string $versionName): string
    {
        $this->checkVersionName($versionName);

        return $this->checkedVersionNames[$versionName];
    }

    /**
     * @throws MigrationException
     */
    public function getVersionFile(string $versionName): string
    {
        $this->checkVersionName($versionName);

        return $this->getVal('migration_dir') . '/' . $versionName . '.php';
    }

    public function getWebDir(): string
    {
        $dir = $this->getVal('migration_dir');
        if (str_starts_with($dir, Module::getDocRoot())) {
            return substr($dir, strlen(Module::getDocRoot()));
        }
        return '';
    }

    public function getLogger()
    {
        return $this->getVal('logger', null);
    }

    public function getBuilders(): array
    {
        return $this->getVal('version_builders', []);
    }

    public function getActualTimestamp(): string
    {
        return date($this->getVal('version_timestamp_format'), time() - 8 * 3600);
    }

    public function showAdminInterface(): bool
    {
        return $this->getVal('show_admin_interface', true);
    }

}
