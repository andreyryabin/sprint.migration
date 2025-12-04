<?php

namespace Sprint\Migration;

use Psr\Log\LoggerInterface;
use Sprint\Migration\Output\LoggerOutput;
use Sprint\Migration\Output\OutputInterface;
use Throwable;

class Output
{
    private static ?Output $instance = null;
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

    public function out(string $msg, ...$vars): void
    {
        foreach ($this->handlers as $handler) {
            $handler->out($msg, ...$vars);
        }
    }

    public function outProgress(string $msg, int $val, int $total): void
    {
        foreach ($this->handlers as $handler) {
            $handler->outProgress($msg, $val, $total);
        }
    }

    public function outNotice(string $msg, ...$vars): void
    {
        foreach ($this->handlers as $handler) {
            $handler->outNotice($msg, ...$vars);
        }
    }

    public function outInfo(string $msg, ...$vars): void
    {
        foreach ($this->handlers as $handler) {
            $handler->outInfo($msg, ...$vars);
        }
    }

    public function outSuccess(string $msg, ...$vars): void
    {
        foreach ($this->handlers as $handler) {
            $handler->outSuccess($msg, ...$vars);
        }
    }

    public function outWarning(string $msg, ...$vars): void
    {
        foreach ($this->handlers as $handler) {
            $handler->outWarning($msg, ...$vars);
        }
    }

    public function outError(string $msg, ...$vars): void
    {
        foreach ($this->handlers as $handler) {
            $handler->outError($msg, ...$vars);
        }
    }

    public function outDiff(array $arr1, array $arr2): void
    {
        foreach ($this->handlers as $handler) {
            $handler->outDiff($arr1, $arr2);
        }
    }

    public function outMessages(array $messages = []): void
    {
        foreach ($this->handlers as $handler) {
            $handler->outMessages($messages);
        }
    }

    public function outException(Throwable $exception): void
    {
        foreach ($this->handlers as $handler) {
            $handler->outException($exception);
        }
    }
}
