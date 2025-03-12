<?php

namespace Sprint\Migration;

use Bitrix\Main\Text\Encoding;

class Locale
{
    public static function loadLocale($lang, $loc)
    {
        global $MESS;
        foreach ($loc as $shortName => $msg) {
            $MESS[self::getMessageName($shortName, $lang)] = $msg;
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
