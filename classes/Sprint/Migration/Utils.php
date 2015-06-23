<?php

namespace Sprint\Migration;

class Utils
{

    private static $userConfig = array();

    public static function isUtf8() {
        return (defined('BX_UTF') && BX_UTF === true);
    }

    public static function getDocRoot(){
        return rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
    }

    public static function getPhpInterfaceDir(){
        if (is_dir(self::getDocRoot() . '/local/php_interface')) {
            return self::getDocRoot() . '/local/php_interface';
        } else {
            return self::getDocRoot() . '/bitrix/php_interface';
        }        
    }

    public static function getModuleDir(){
        if (is_dir(self::getDocRoot() . '/local/modules/sprint.migration')) {
            return self::getDocRoot() . '/local/modules/sprint.migration';
        } else {
            return self::getDocRoot() . '/bitrix/modules/sprint.migration';
        }        
    }

    public static function includeLangFile() {
        global $MESS;

        if (self::isUtf8()){
            include self::getModuleDir() . '/localization/ru_utf8.php';
        } else {
            include self::getModuleDir() . '/localization/ru_windows1251.php';
        }
    }

    public static function getVersionTemplateFile(){
        if (self::getUserConfigVal('migration_template') && is_file(Utils::getDocRoot() . self::getUserConfigVal('migration_template'))){
            return Utils::getDocRoot() . self::getUserConfigVal('migration_template');
        } else {
            return Utils::getModuleDir() . '/templates/version.php';
        }
    }
    public static function getMigrationDir(){
        if (self::getUserConfigVal('migration_dir') && is_dir(Utils::getDocRoot() . self::getUserConfigVal('migration_dir'))){
            return Utils::getDocRoot() . self::getUserConfigVal('migration_dir');
        }

        $dir = Utils::getPhpInterfaceDir() . '/migrations';
        if (!is_dir($dir)){
            mkdir($dir , BX_DIR_PERMISSIONS);
        }
        return $dir;
    }

    public static function getUserConfigVal($val, $default = ''){
        if (empty(self::$userConfig)){
            $file = Utils::getPhpInterfaceDir() . '/migrations.cfg.php';
            if (is_file($file)){
                self::$userConfig = include $file;
            }
        }

        return isset(self::$userConfig[$val]) ? self::$userConfig[$val] : $default;
    }

}



