<?php

namespace Sprint\Migration\Output;

use Throwable;

trait OutputTrait
{
    protected function out(string $msg, ...$vars): void
    {
        OutputFactory::getInstance()->out($msg, ...$vars);
    }

    protected function outProgress(string $msg, int $val, int $total): void
    {
        OutputFactory::getInstance()->outProgress($msg, $val, $total);
    }

    protected function outNotice(string $msg, ...$vars): void
    {
        OutputFactory::getInstance()->outNotice($msg, ...$vars);
    }

    protected function outInfo(string $msg, ...$vars): void
    {
        OutputFactory::getInstance()->outInfo($msg, ...$vars);
    }

    protected function outSuccess(string $msg, ...$vars): void
    {
        OutputFactory::getInstance()->outSuccess($msg, ...$vars);
    }

    protected function outWarning(string $msg, ...$vars): void
    {
        OutputFactory::getInstance()->outWarning($msg, ...$vars);
    }

    protected function outError(string $msg, ...$vars): void
    {
        OutputFactory::getInstance()->outError($msg, ...$vars);
    }

    protected function outDiff(array $arr1, array $arr2): void
    {
        OutputFactory::getInstance()->outDiff($arr1, $arr2);
    }

    protected function outMessages(array $messages = []): void
    {
        OutputFactory::getInstance()->outMessages($messages);
    }

    protected function outException(Throwable $exception): void
    {
        OutputFactory::getInstance()->outException($exception);
    }
}
