<?php

namespace Sprint\Migration;

class Locale
{
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

    public static function loadLocale($lang, $loc)
    {
        global $MESS;
        foreach ($loc as $shortName => $msg) {
            $MESS[self::getMessageName($shortName, $lang)] = self::convertToWin1251IfNeed($msg);
        }
    }

    public static function getMessageName($shortName, $lang = false)
    {
        $lang = ($lang) ? $lang : self::getLang();

        return strtoupper('SPRINT_MIGRATION_' . $lang . '_' . $shortName);
    }

    public static function getLang()
    {
        return defined('LANGUAGE_ID') ? LANGUAGE_ID : 'ru';
    }

    public static function getMessage($shortName, $replaces = [])
    {
        $msg = GetMessage(self::getMessageName($shortName), $replaces);
        return ($msg) ? : $shortName;
    }
}
