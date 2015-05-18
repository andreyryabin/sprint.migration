<?php

namespace Sprint\Migration;

class Loc
{

    public static function includeLangFile() {
        global $MESS;

        if (self::isUtf8()){
            include __DIR__ . '/../../../localization/ru_utf8.php';
        } else {
            include __DIR__ . '/../../../localization/ru_windows1251.php';
        }
    }

    protected static function isUtf8() {
        return (defined('BX_UTF') && BX_UTF === true);
    }

}



