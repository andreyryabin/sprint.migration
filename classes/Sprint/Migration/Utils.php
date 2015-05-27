<?php

namespace Sprint\Migration;

class Utils
{

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

}



