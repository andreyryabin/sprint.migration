<?php

namespace Sprint\Migration\Output;

use Psr\Log\LoggerInterface;
use Throwable;

class CompositeOutput implements OutputInterface
{
    /**
     * @var OutputInterface[]
     */
    private array $channels;

    public function __construct(OutputInterface $output, ?LoggerInterface $logger = null)
    {
        $this->channels = array_filter([
            $output,
            $logger ? new LoggerOutput($logger) : null,
        ]);
    }

    public function out(string $msg, ...$vars): void
    {
        foreach ($this->channels as $channel) {
            $channel->out($msg, ...$vars);
        }
    }

    public function outProgress(string $msg, int $val, int $total): void
    {
        foreach ($this->channels as $channel) {
            $channel->outProgress($msg, $val, $total);
        }
    }

    public function outNotice(string $msg, ...$vars): void
    {
        foreach ($this->channels as $channel) {
            $channel->outNotice($msg, ...$vars);
        }
    }

    public function outInfo(string $msg, ...$vars): void
    {
        foreach ($this->channels as $channel) {
            $channel->outInfo($msg, ...$vars);
        }
    }

    public function outSuccess(string $msg, ...$vars): void
    {
        foreach ($this->channels as $channel) {
            $channel->outSuccess($msg, ...$vars);
        }
    }

    public function outWarning(string $msg, ...$vars): void
    {
        foreach ($this->channels as $channel) {
            $channel->outWarning($msg, ...$vars);
        }
    }

    public function outError(string $msg, ...$vars): void
    {
        foreach ($this->channels as $channel) {
            $channel->outError($msg, ...$vars);
        }
    }

    public function outDiff(array $arr1, array $arr2): void
    {
        foreach ($this->channels as $channel) {
            $channel->outDiff($arr1, $arr2);
        }
    }

    public function outException(Throwable $exception): void
    {
        foreach ($this->channels as $channel) {
            $channel->outException($exception);
        }
    }
}
