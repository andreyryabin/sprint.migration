<?php

namespace Sprint\Migration\Output;

use Psr\Log\LoggerInterface;
use Throwable;

class LoggerOutput implements OutputInterface
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function out(string $msg, ...$vars): void
    {
        $this->logger->debug(sprintf($msg, ...$vars));
    }

    public function outProgress(string $msg, int $val, int $total): void
    {
        $this->logger->debug($msg . ' ' . $val . ' / ' . $total);
    }

    public function outNotice(string $msg, ...$vars): void
    {
        $this->logger->notice(sprintf($msg, ...$vars));
    }

    public function outInfo(string $msg, ...$vars): void
    {
        $this->logger->info(sprintf($msg, ...$vars));
    }

    public function outSuccess(string $msg, ...$vars): void
    {
        $this->logger->notice(sprintf($msg, ...$vars));
    }

    public function outWarning(string $msg, ...$vars): void
    {
        $this->logger->warning(sprintf($msg, ...$vars));
    }

    public function outError(string $msg, ...$vars): void
    {
        $this->logger->error(sprintf($msg, ...$vars));
    }

    public function outDiff(array $arr1, array $arr2): void
    {
        // TODO: Implement outDiff() method.
    }

    public function outException(Throwable $exception): void
    {
        $this->logger->error($exception->getMessage());
    }

    public function outMessages(array $messages = []): void
    {
        foreach ($messages as $val) {
            if ($val['success']) {
                $this->logger->notice($val['message']);
            } else {
                $this->logger->error($val['message']);
            }
        }
    }
}
