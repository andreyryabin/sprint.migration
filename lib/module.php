<?php

namespace Sprint\Migration;

use COption;
use Sprint\Migration\Exceptions\MigrationException;

class Module
{
    const ID = 'sprint.migration';
    const EXCHANGE_VERSION = 2;
    private static string $version = '';

    public static function getDbOption($name, $default = '')
    {
        return COption::GetOptionString(Module::ID, $name, $default);
    }

    public static function setDbOption($name, $value)
    {
        if ($value != COption::GetOptionString(Module::ID, $name)) {
            COption::SetOptionString(Module::ID, $name, $value);
        }
    }

    public static function removeDbOption($name)
    {
        COption::RemoveOption(Module::ID, $name);
    }

    public static function removeDbOptions()
    {
        COption::RemoveOption(Module::ID);
    }

    public static function getDocRoot(): string
    {
        return rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
    }

    public static function getPhpInterfaceDir($absolute = true): string
    {
        $root = $absolute ? self::getDocRoot() : '';

        if (is_dir(self::getDocRoot() . '/local/php_interface')) {
            return $root . '/local/php_interface';
        } else {
            return $root . '/bitrix/php_interface';
        }
    }

    public static function getModuleDir($absolute = true): string
    {
        $root = $absolute ? self::getDocRoot() : '';

        if (is_file(self::getDocRoot() . '/local/modules/' . Module::ID . '/include.php')) {
            return $root . '/local/modules/' . Module::ID;
        } else {
            return $root . '/bitrix/modules/' . Module::ID;
        }
    }

    public static function getModuleTemplateFile(string $name): string
    {
        return Module::getModuleDir() . '/templates/' . $name . '.php';
    }

    /**
     * @throws MigrationException
     */
    public static function createDir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }

        if (!is_dir($dir)) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_CANT_CREATE_DIRECTORY',
                    [
                        '#NAME#' => $dir,
                    ]
                )
            );
        }

        return $dir;
    }

    public static function deletePath($dir): void
    {
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    self::deletePath("$dir/$file");
                }
            }
            rmdir($dir);
        } elseif (file_exists($dir)) {
            unlink($dir);
        }
    }

    public static function movePath(string $from, string $to): void
    {
        rename($from, $to);
    }

    public static function getVersion(): string
    {
        if (!self::$version) {
            $arModuleVersion = [];
            include self::getModuleDir() . '/install/version.php';
            self::$version = (string)($arModuleVersion['VERSION'] ?? '');
        }
        return self::$version;
    }

    /**
     * @throws MigrationException
     */
    public static function checkHealth(): void
    {
        if (!function_exists('json_encode')) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_JSON_NOT_SUPPORTED'
                )
            );
        }

        if (version_compare(PHP_VERSION, '8.1', '<')) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_PHP_NOT_SUPPORTED',
                    [
                        '#NAME#' => PHP_VERSION,
                    ]
                )
            );
        }

        if (
            is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/' . Module::ID . '/include.php')
            && is_file($_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . Module::ID . '/include.php')
        ) {
            throw new MigrationException('module installed to bitrix and local folder');
        }
    }
}



