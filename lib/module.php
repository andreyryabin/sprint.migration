<?php

namespace Sprint\Migration;

use COption;
use Exception;

/**
 *
 * В этом классе у свойств не надо указывать тип
 *  ради совместимости с php < 7.4, чтобы админка
 *  могла корректно показать фразу PHP 7.3 не поддерживается
 */
class Module
{
    const ID = 'sprint.migration';
    /**
     * @var string
     */
    private static $version = '';
    /**
     * @var array
     */
    private static $defaultOptions = [
        'show_schemas' => 'N',
        'show_support' => 'N',
    ];

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

    public static function checkDbOption(string $name, bool $checked)
    {
        self::setDbOption($name, $checked ? 'Y' : 'N');
    }

    public static function isDbOptionChecked(string $name)
    {
        return self::getDbOption($name, self::$defaultOptions[$name]) == 'Y';
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

    /**
     * @param $dir
     *
     * @throws Exception
     * @return mixed
     */
    public static function createDir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }

        if (!is_dir($dir)) {
            throw new Exception(
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

    public static function deletePath($dir)
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

    public static function movePath(string $from, string $to)
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
     * @throws Exception
     */
    public static function checkHealth()
    {
        if (isset($GLOBALS['DBType']) && strtolower($GLOBALS['DBType']) == 'mssql') {
            throw new Exception(
                Locale::getMessage(
                    'ERR_MSSQL_NOT_SUPPORTED'
                )
            );
        }

        if (!function_exists('json_encode')) {
            throw new Exception(
                Locale::getMessage(
                    'ERR_JSON_NOT_SUPPORTED'
                )
            );
        }

        if (version_compare(PHP_VERSION, '7.4', '<')) {
            throw new Exception(
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
            throw new Exception('module installed to bitrix and local folder');
        }
    }
}



