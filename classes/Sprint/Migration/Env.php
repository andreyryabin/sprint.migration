<?php

namespace Sprint\Migration;

class Env
{

    private static $userConfig = array();

    public static function isWin1251() {
        return (defined('BX_UTF') && BX_UTF === true) ? 0 : 1;
    }

    /**
     * @return \CDatabase
     */
    public static function getDb() {
        return $GLOBALS['DB'];
    }

    /** @return \CMain */
    public static function getApp() {
        return $GLOBALS['APPLICATION'];
    }


    public static function isMssql() {
        return ($GLOBALS['DBType'] == 'mssql');
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

        foreach ($loc as $key => $msg){
            if (self::isWin1251()){
                $msg = iconv('utf-8', 'windows-1251//IGNORE', $msg);
            }
            $MESS[$key] = $msg;
        }
    }

    public static function getVersionTemplateFile(){
        if (self::getUserConfigVal('migration_template') && is_file(self::getDocRoot() . self::getUserConfigVal('migration_template'))){
            return self::getDocRoot() . self::getUserConfigVal('migration_template');
        } else {
            return self::getModuleDir() . '/templates/version.php';
        }
    }
    public static function getMigrationDir(){
        if (self::getUserConfigVal('migration_dir') && is_dir(self::getDocRoot() . self::getUserConfigVal('migration_dir'))){
            $dir = self::getDocRoot() . self::getUserConfigVal('migration_dir');

        } else {
            $dir = self::getPhpInterfaceDir() . '/migrations';
            if (!is_dir($dir)){
                mkdir($dir , BX_DIR_PERMISSIONS);
            }
        }
        return realpath($dir);
    }

    public static function getUserConfigVal($val, $default = ''){
        if (empty(self::$userConfig)){
            $file = self::getPhpInterfaceDir() . '/migrations.cfg.php';
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



