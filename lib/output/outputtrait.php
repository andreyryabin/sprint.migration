<?php

namespace Sprint\Migration\Output;

use Throwable;

trait OutputTrait
{

    private ?OutputInterface $output = null;

    public function setOutput(?OutputInterface $output = null): void
    {
        $this->output = $output;
    }

    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    protected function out(string $msg, ...$vars): void
    {
        $this->output?->out($msg, ...$vars);
    }

    protected function outProgress(string $msg, int $val, int $total): void
    {
        $this->output?->outProgress($msg, $val, $total);
    }

    protected function outNotice(string $msg, ...$vars): void
    {
        $this->output?->outNotice($msg, ...$vars);
    }

    protected function outInfo(string $msg, ...$vars): void
    {
        $this->output?->outInfo($msg, ...$vars);
    }

    protected function outSuccess(string $msg, ...$vars): void
    {
        $this->output?->outSuccess($msg, ...$vars);
    }

    protected function outWarning(string $msg, ...$vars): void
    {
        $this->output?->outWarning($msg, ...$vars);
    }

    protected function outError(string $msg, ...$vars): void
    {
        $this->output?->outError($msg, ...$vars);
    }

    protected function outDiff(array $arr1, array $arr2): void
    {
        $this->output?->outDiff($arr1, $arr2);
    }

    protected function outException(Throwable $exception): void
    {
        $this->output?->outException($exception);
    }
}
