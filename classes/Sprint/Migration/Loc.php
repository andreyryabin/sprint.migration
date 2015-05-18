<?php

namespace Sprint\Migration;

class Loc
{

    protected static $messages = array();

    public static function getMessage($code) {
        self::loadLoc();
        return isset(self::$messages[$code]) ? self::$messages[$code] : $code;
    }

    protected static function loadLoc() {
        if (empty(self::$messages)) {
            $MESS = array();

            if (self::isUtf8()){
                include __DIR__ . '/../../../localization/ru_utf8.php';
            } else {
                include __DIR__ . '/../../../localization/ru_windows1251.php';
            }

            self::$messages = $MESS;
        }
    }

    protected static function isUtf8() {
        return (defined('BX_UTF') && BX_UTF === true);
    }

}



