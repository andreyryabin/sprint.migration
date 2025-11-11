<?php

namespace Sprint\Migration;

class Locale
{
    public static function loadLocale(string $lang, array $loc): void
    {
        global $MESS;
        foreach ($loc as $shortName => $msg) {
            $MESS[self::getMessageName($shortName, $lang)] = $msg;
        }
    }

    public static function getMessageName(string $shortName, string $lang = 'ru'): string
    {
        return strtoupper('SPRINT_MIGRATION_' . $lang . '_' . $shortName);
    }

    public static function getLang()
    {
        return defined('LANGUAGE_ID') ? LANGUAGE_ID : 'ru';
    }

    public static function getMessage(string $shortName, array $replaces = [], $default = '')
    {
        $replaces = array_filter($replaces, fn($v) => is_scalar($v));

        $lang = self::getLang();

        $msg = GetMessage(self::getMessageName($shortName, $lang), $replaces);

        return ($msg) ?: ($default ?: $shortName);
    }
}
