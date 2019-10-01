<?php

namespace Sprint\Migration;

class Locale
{
    private static $localeLoaded = false;

    public static function isWin1251()
    {
        return (defined('BX_UTF') && BX_UTF === true) ? 0 : 1;
    }

    public static function convertToWin1251IfNeed($msg)
    {
        if (self::isWin1251() && self::detectUtf8($msg)) {
            $msg = iconv('utf-8', 'windows-1251//IGNORE', $msg);
        }
        return $msg;
    }

    public static function convertToUtf8IfNeed($msg)
    {
        if (self::isWin1251() && !self::detectUtf8($msg)) {
            $msg = iconv('windows-1251', 'utf-8//IGNORE', $msg);
        }
        return $msg;
    }

    protected static function detectUtf8($msg)
    {
        return (md5($msg) == md5(iconv('utf-8', 'utf-8', $msg))) ? 1 : 0;
    }

    public static function loadLocale($loc)
    {
        global $MESS;

        if (!self::$localeLoaded) {
            foreach ($loc as $key => $msg) {
                $MESS['SPRINT_MIGRATION_' . $key] = self::convertToWin1251IfNeed($msg);
            }
        }

    }

    public static function getMessage($name, $aReplace = null)
    {
        return GetMessage('SPRINT_MIGRATION_' . $name, $aReplace);
    }
}