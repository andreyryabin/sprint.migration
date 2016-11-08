<?php

namespace Sprint\Migration;

class Module
{

    public static function getDbOption($name, $default = '') {
        return \COption::GetOptionString('sprint.migration', $name, $default);
    }

    public static function setDbOption($name, $value) {
        if ($value != \COption::GetOptionString('sprint.migration', $name, '')) {
            \COption::SetOptionString('sprint.migration', $name, $value);
        }
    }

    public static function removeDbOptions(){
        \COption::RemoveOption('sprint.migration');
    }

    public static function getDocRoot() {
        return rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR);
    }

    public static function getPhpInterfaceDir() {
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

    public static function getVersion() {
        $arModuleVersion = array();
        /** @noinspection PhpIncludeInspection */
        include self::getModuleDir() . '/install/version.php';
        return isset($arModuleVersion['VERSION']) ? $arModuleVersion['VERSION'] : '';
    }

}



