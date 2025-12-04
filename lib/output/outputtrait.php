<?php

namespace Sprint\Migration\Output;

use Sprint\Migration\Output;
use Throwable;

trait OutputTrait
{
    protected function out(string $msg, ...$vars): void
    {
        Output::getInstance()->out($msg, ...$vars);
    }

    protected function outProgress(string $msg, int $val, int $total): void
    {
        Output::getInstance()->outProgress($msg, $val, $total);
    }

    protected function outNotice(string $msg, ...$vars): void
    {
        Output::getInstance()->outNotice($msg, ...$vars);
    }

    protected function outInfo(string $msg, ...$vars): void
    {
        Output::getInstance()->outInfo($msg, ...$vars);
    }

    protected function outSuccess(string $msg, ...$vars): void
    {
        Output::getInstance()->outSuccess($msg, ...$vars);
    }

    protected function outWarning(string $msg, ...$vars): void
    {
        Output::getInstance()->outWarning($msg, ...$vars);
    }

    protected function outError(string $msg, ...$vars): void
    {
        Output::getInstance()->outError($msg, ...$vars);
    }

    protected function outDiff(array $arr1, array $arr2): void
    {
        Output::getInstance()->outDiff($arr1, $arr2);
    }

    protected function outMessages(array $messages = []): void
    {
        Output::getInstance()->outMessages($messages);
    }

    protected function outException(Throwable $exception): void
    {
        Output::getInstance()->outException($exception);
    }
}
