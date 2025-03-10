<?php

namespace Sprint\Migration;

use Bitrix\Main\Text\Encoding;

class Locale
{
    public static function isWin1251(): bool
    {
        return !(defined('BX_UTF') && BX_UTF === true);
    }

    public static function convertToWin1251IfNeed($msg)
    {
        if (self::isWin1251() && Encoding::detectUtf8($msg)) {
            $msg = Encoding::convertEncoding($msg, 'utf-8', 'windows-1251//IGNORE');
        }
        return $msg;
    }

    public static function convertToUtf8IfNeed($msg)
    {
        if (self::isWin1251() && !Encoding::detectUtf8($msg)) {
            $msg = Encoding::convertEncoding($msg, 'windows-1251', 'utf-8//IGNORE');
        }
        return $msg;
    }

    public static function loadLocale($lang, $loc)
    {
        global $MESS;
        foreach ($loc as $shortName => $msg) {
            $MESS[self::getMessageName($shortName, $lang)] = self::convertToWin1251IfNeed($msg);
        }
    }

    public static function getMessageName($shortName, $lang = false): string
    {
        $lang = ($lang) ?: self::getLang();

        return strtoupper('SPRINT_MIGRATION_' . $lang . '_' . $shortName);
    }

    public static function getLang()
    {
        return defined('LANGUAGE_ID') ? LANGUAGE_ID : 'ru';
    }

    public static function getMessage($shortName, $replaces = [], $default = '')
    {
        $msg = GetMessage(self::getMessageName($shortName), $replaces);
        return ($msg) ?: ($default ?: $shortName);
    }
}
