<?php

namespace Sprint\Migration;

class Module
{

    private static $fileOptions = array();

    private static $localeLoaded = false;

    public static function isWin1251() {
        return (defined('BX_UTF') && BX_UTF === true) ? 0 : 1;
    }

    /**
     * @return \CDatabase
     */
    public static function getDb() {
        return $GLOBALS['DB'];
    }

    public static function getDbName() {
        return $GLOBALS['DBName'];
    }


    public static function isMssql() {
        return ($GLOBALS['DBType'] == 'mssql');
    }

    public static function getDbOption($name, $default = '') {
        return \COption::GetOptionString('sprint.migration', $name, $default);
    }

    public static function setDbOption($name, $value) {
        if ($value != \COption::GetOptionString('sprint.migration', $name, '')) {
            \COption::SetOptionString('sprint.migration', $name, $value);
        }
    }

    public static function getFileOption($val, $default = '') {
        if (empty(self::$fileOptions)) {
            $file = self::getPhpInterfaceDir() . '/migrations.cfg.php';
            if (is_file($file)) {
                /** @noinspection PhpIncludeInspection */
                self::$fileOptions = include $file;
            }
        }

        return !empty(self::$fileOptions[$val]) ? self::$fileOptions[$val] : $default;
    }


    protected static function getDocRoot() {
        return rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
    }

    protected static function getPhpInterfaceDir() {
        if (is_dir(self::getDocRoot() . '/local/php_interface')) {
            return self::getDocRoot() . '/local/php_interface';
        } else {
            return self::getDocRoot() . '/bitrix/php_interface';
        }
    }

    public static function getModuleDir() {
        if (is_file(self::getDocRoot() . '/local/modules/sprint.migration/include.php')) {
            return self::getDocRoot() . '/local/modules/sprint.migration';
        } else {
            return self::getDocRoot() . '/bitrix/modules/sprint.migration';
        }
    }

    public static function getUpgradeDir() {
        return self::getModuleDir() . '/upgrades';
    }

    public static function getMigrationTemplate() {
        if (self::getFileOption('migration_template') && is_file(self::getDocRoot() . self::getFileOption('migration_template'))) {
            return self::getDocRoot() . self::getFileOption('migration_template');
        } else {
            return self::getModuleDir() . '/templates/version.php';
        }
    }

    public static function getMigrationTable() {
        return self::getFileOption('migration_table', 'sprint_migration_versions');
    }

    public static function getMigrationExtendClass() {
        return self::getFileOption('migration_extend_class', 'Version');
    }

    public static function getMigrationDir() {
        if (self::getFileOption('migration_dir') && is_dir(self::getDocRoot() . self::getFileOption('migration_dir'))) {
            $dir = self::getDocRoot() . self::getFileOption('migration_dir');

        } else {
            $dir = self::getPhpInterfaceDir() . '/migrations';
            if (!is_dir($dir)) {
                mkdir($dir, BX_DIR_PERMISSIONS);
            }
        }
        return realpath($dir);
    }

    public static function getMigrationWebDir() {
        $d1 = self::getMigrationDir();
        $d2 = self::getDocRoot();
        return (false !== strpos($d1, $d2)) ? str_replace($d2, '', $d1) : false;
    }

    public static function getVersion() {
        $arModuleVersion = array();
        /** @noinspection PhpIncludeInspection */
        include self::getModuleDir() . '/install/version.php';
        return isset($arModuleVersion['VERSION']) ? $arModuleVersion['VERSION'] : '';
    }

    public static function loadLocale($loc) {
        require_once __DIR__ . '/Out.php';
        global $MESS;

        if (!self::$localeLoaded) {
            foreach ($loc as $key => $msg) {
                $MESS[$key] = Out::convertToWin1251IfNeed($msg);
            }
        }

    }

}



