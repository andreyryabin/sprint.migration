<?php

namespace Sprint\Migration\Output;


class OutputFactory
{
    public static function create(): OutputInterface
    {
        if (self::isCli()) {
            return new ConsoleOutput();
        }
        return new HtmlOutput();
    }

    private static function isCli(): bool
    {
        return PHP_SAPI === 'cli' || defined('STDIN');
    }
}
