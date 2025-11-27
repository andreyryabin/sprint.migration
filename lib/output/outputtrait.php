<?php

namespace Sprint\Migration\Output;

use Throwable;

trait OutputTrait
{
    private ?OutputInterface $output = null;

    private function getOutput(): OutputInterface
    {
        if ($this->output === null) {
            $this->output = OutputFactory::create();
        }
        return $this->output;
    }

    protected function out(string $msg, ...$vars): void
    {
        $this->getOutput()->out($msg, ...$vars);
    }

    protected function outProgress(string $msg, $val, $total): void
    {
        $this->getOutput()->outProgress(...func_get_args());
    }

    protected function outNotice(string $msg, ...$vars): void
    {
        $this->getOutput()->outNotice(...func_get_args());
    }

    protected function outInfo(string $msg, ...$vars): void
    {
        $this->getOutput()->outInfo(...func_get_args());
    }

    protected function outSuccess(string $msg, ...$vars): void
    {
        $this->getOutput()->outSuccess(...func_get_args());
    }

    protected function outWarning(string $msg, ...$vars): void
    {
        $this->getOutput()->outWarning(...func_get_args());
    }

    protected function outError(string $msg, ...$vars): void
    {
        $this->getOutput()->outError(...func_get_args());
    }

    protected function outDiff(array $arr1, array $arr2): void
    {
        $this->getOutput()->outDiff(...func_get_args());
    }

    protected function outMessages(array $messages = []): void
    {
        $this->getOutput()->outMessages(...func_get_args());
    }

    protected function outException(Throwable $exception): void
    {
        $this->getOutput()->outException(...func_get_args());
    }
}
