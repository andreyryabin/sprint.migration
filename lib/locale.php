<?php

namespace Sprint\Migration;

class Locale
{
    public static function getDefaultLang(): string
    {
        $lang = defined('LANGUAGE_ID') ? LANGUAGE_ID : 'ru';

        return $lang === 'en' ? 'en' : 'ru';
    }

    public static function getMessage(string $shortName, array $replaces = [], $default = ''): string
    {
        $replaces = array_filter($replaces, fn($v) => is_scalar($v));

        $msg = (string) GetMessage("SPRINT_MIGRATION_$shortName", $replaces);

        return ($msg) ?: ($default ?: $shortName);
    }

    public static function getPhpVersionMessage(): string
    {
        $v = defined('PHP_VERSION') ? PHP_VERSION : '';
        return self::getMessage('PHP_VERSION', ['#NAME#' => $v]);
    }

    public static function getModuleVersionMessage(): string
    {
        $v = Module::getVersion();
        return self::getMessage('MODULE_VERSION', ['#NAME#' => $v]);
    }

    public static function getModuleIdentyMessage(): string
    {
        $v = Module::ID;
        return self::getMessage('MODULE_IDENTY', ['#ID#' => $v]);
    }

    public static function getBitrixVersionMessage(): string
    {
        $v = defined('SM_VERSION') ? SM_VERSION : '';
        return self::getMessage('BITRIX_VERSION', ['#NAME#' => $v]);
    }

    public static function getCurrentUserMessage(int $id, string $login): string
    {
        return self::getMessage('CURRENT_USER', ['#ID#' => $id, '#LOGIN#' => $login]);
    }
}
