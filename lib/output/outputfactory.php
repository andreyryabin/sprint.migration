<?php

namespace Sprint\Migration\Output;

use Psr\Log\LoggerInterface;
use Sprint\Migration\Logger\LoggerOutput;
use Throwable;

class OutputFactory
{
    private static ?OutputFactory $instance = null;
    /**
     * @var OutputInterface[]
     */
    private array $handlers = [];

    public static function getInstance(): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function addOutput(?OutputInterface $output = null): static
    {
        if ($output) {
            $this->handlers[] = $output;
        }

        return $this;
    }

    public function addLogger(?LoggerInterface $logger = null): static
    {
        if ($logger) {
            $this->handlers[] = new LoggerOutput($logger);
        }

        return $this;
    }

    private function each(\Closure $fn): void
    {
        foreach ($this->handlers as $handler) {
            $fn($handler);
        }
    }

    public function out(string $msg, ...$vars): void
    {
        $this->each(fn(OutputInterface $output) => $output->out($msg, ...$vars));
    }

    public function outProgress(string $msg, int $val, int $total): void
    {
        $this->each(fn(OutputInterface $output) => $output->outProgress($msg, $val, $total));
    }

    public function outNotice(string $msg, ...$vars): void
    {
        $this->each(fn(OutputInterface $output) => $output->outNotice($msg, ...$vars));
    }

    public function outInfo(string $msg, ...$vars): void
    {
        $this->each(fn(OutputInterface $output) => $output->outInfo($msg, ...$vars));
    }

    public function outSuccess(string $msg, ...$vars): void
    {
        $this->each(fn(OutputInterface $output) => $output->outSuccess($msg, ...$vars));
    }

    public function outWarning(string $msg, ...$vars): void
    {
        $this->each(fn(OutputInterface $output) => $output->outWarning($msg, ...$vars));
    }

    public function outError(string $msg, ...$vars): void
    {
        $this->each(fn(OutputInterface $output) => $output->outError($msg, ...$vars));
    }

    public function outDiff(array $arr1, array $arr2): void
    {
        $this->each(fn(OutputInterface $output) => $output->outDiff($arr1, $arr2));
    }

    public function outMessages(array $messages = []): void
    {
        $this->each(fn(OutputInterface $output) => $output->outMessages($messages));
    }

    public function outException(Throwable $exception): void
    {
        $this->each(fn(OutputInterface $output) => $output->outException($exception));
    }
}
