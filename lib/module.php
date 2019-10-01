<?php

namespace Sprint\Migration;

use COption;
use Exception;

class Module
{

    public static function getDbOption($name, $default = '')
    {
        return COption::GetOptionString('sprint.migration', $name, $default);
    }

    public static function setDbOption($name, $value)
    {
        if ($value != COption::GetOptionString('sprint.migration', $name, '')) {
            COption::SetOptionString('sprint.migration', $name, $value);
        }
    }

    public static function removeDbOption($name)
    {
        COption::RemoveOption('sprint.migration', $name);
    }

    public static function removeDbOptions()
    {
        COption::RemoveOption('sprint.migration');
    }

    public static function getDocRoot()
    {
        return rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
    }

    public static function getPhpInterfaceDir()
    {
        if (is_dir(self::getDocRoot() . '/local/php_interface')) {
            return self::getDocRoot() . '/local/php_interface';
        } else {
            return self::getDocRoot() . '/bitrix/php_interface';
        }
    }

    public static function getModuleDir()
    {
        if (is_file(self::getDocRoot() . '/local/modules/sprint.migration/include.php')) {
            return self::getDocRoot() . '/local/modules/sprint.migration';
        } else {
            return self::getDocRoot() . '/bitrix/modules/sprint.migration';
        }
    }

    public static function getRelativeDir($dir)
    {
        $docroot = Module::getDocRoot();
        $docroot = str_replace("/", DIRECTORY_SEPARATOR, $docroot);
        $dir = str_replace("/", DIRECTORY_SEPARATOR, $dir);
        if (strpos($dir, $docroot) === 0) {
            $dir = substr($dir, strlen($docroot));
        }
        return $dir;
    }

    /**
     * @param $dir
     * @throws Exception
     * @return mixed
     */
    public static function createDir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }

        if (!is_dir($dir)) {
            Throw new Exception("Can't create directory $dir");
        }

        return $dir;
    }

    public static function getVersion()
    {
        $arModuleVersion = [];
        /** @noinspection PhpIncludeInspection */
        include self::getModuleDir() . '/install/version.php';
        return isset($arModuleVersion['VERSION']) ? $arModuleVersion['VERSION'] : '';
    }

    /**
     * @throws Exception
     */
    public static function checkHealth()
    {
        if (isset($GLOBALS['DBType']) && strtolower($GLOBALS['DBType']) == 'mssql') {
            Throw new Exception('mssql not supported');
        }

        if (!function_exists('json_encode')) {
            Throw new Exception('json functions not supported');
        }

        if (version_compare(PHP_VERSION, '5.6', '<')) {
            Throw new Exception(PHP_VERSION . ' not supported');
        }

        if (
            is_file($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/sprint.migration/include.php') &&
            is_file($_SERVER['DOCUMENT_ROOT'] . '/local/modules/sprint.migration/include.php')
        ) {
            Throw new Exception('module installed to bitrix and local folder');
        }
    }
}



