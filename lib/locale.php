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

    public static function getDefaultLang(): string
    {
        $lang = defined('LANGUAGE_ID') ? LANGUAGE_ID : 'ru';

        return $lang === 'en' ? 'en' : 'ru';
    }

    public static function getMessage(string $shortName, array $replaces = [], $default = '')
    {
        $replaces = array_filter($replaces, fn($v) => is_scalar($v));

        $lang = self::getDefaultLang();

        $msg = GetMessage(self::getMessageName($shortName, $lang), $replaces);

        return ($msg) ?: ($default ?: $shortName);
    }

    public static function loadDefault(): void
    {
        $lang = self::getDefaultLang();

        $files = [
            'ru' => __DIR__ . '/../locale/ru.php',
            'en' => __DIR__ . '/../locale/en.php',
        ];

        if (isset($files[$lang])) {
            include($files[$lang]);
        } else {
            include($files['ru']);
        }
    }
}
