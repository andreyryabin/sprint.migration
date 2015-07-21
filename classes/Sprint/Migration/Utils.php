<?php

namespace Sprint\Migration;

class Utils
{

    private static $userConfig = array();

    public static function isUtf8() {
        return (defined('BX_UTF') && BX_UTF === true);
    }

    public static function convertToUtf8IfNeed($msg){
        if (!Utils::isUtf8() && function_exists('iconv')){
            $msg = iconv('windows-1251', 'utf-8', $msg);
        }
        return $msg;
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

        $loc = array();
        include self::getModuleDir() . '/localization/ru_utf8.php';

        $isWin = (!self::isUtf8() && function_exists('iconv')) ? 1 : 0;

        foreach ($loc as $key => $val){
            $val = $isWin ? iconv('utf-8', 'windows-1251//IGNORE', $val) : $val;
            $MESS[$key] = $val;
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
            $dir = Utils::getDocRoot() . self::getUserConfigVal('migration_dir');

        } else {
            $dir = Utils::getPhpInterfaceDir() . '/migrations';
            if (!is_dir($dir)){
                mkdir($dir , BX_DIR_PERMISSIONS);
            }
        }
        return realpath($dir);
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


    public static function getMigrationWebDir(){
        $d1 = self::getMigrationDir();
        $d2 = $_SERVER['DOCUMENT_ROOT'];
        return (false !== strpos($d1, $d2)) ? str_replace($d2, '', $d1) : false;
    }

}



