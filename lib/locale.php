<?php

namespace Sprint\Migration;

class Locale
{
    public static function getDefaultLang(): string
    {
        $lang = defined('LANGUAGE_ID') ? LANGUAGE_ID : 'ru';

        return $lang === 'en' ? 'en' : 'ru';
    }

    public static function getMessage(string $shortName, array $replaces = [], $default = '')
    {
        $replaces = array_filter($replaces, fn($v) => is_scalar($v));

       $msg = GetMessage("SPRINT_MIGRATION_$shortName", $replaces);

        return ($msg) ?: ($default ?: $shortName);
    }
}
