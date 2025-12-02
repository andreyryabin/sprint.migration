<?php

namespace Sprint\Migration\Output;

use Throwable;

interface OutputInterface
{
    public function out(string $msg, ...$vars): void;

    public function outProgress(string $msg, int $val, int $total): void;

    public function outNotice(string $msg, ...$vars): void;

    public function outInfo(string $msg, ...$vars): void;

    public function outSuccess(string $msg, ...$vars): void;

    public function outWarning(string $msg, ...$vars): void;

    public function outError(string $msg, ...$vars): void;

    public function outDiff(array $arr1, array $arr2): void;

    public function outException(Throwable $exception): void;

    public function outMessages(array $messages = []): void;
}
