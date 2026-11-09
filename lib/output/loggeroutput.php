<?php

namespace Sprint\Migration\Output;

use Psr\Log\LoggerInterface;

class LoggerOutput extends AbstractOutput
{

    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    protected function writeMessage(string $msg, string $type): void
    {
        match ($type) {
            'info' => $this->logger->info($msg),
            'notice', 'success' => $this->logger->notice($msg),
            'warning' => $this->logger->warning($msg),
            'error' => $this->logger->error($msg),
            default => $this->logger->debug($msg),
        };
    }

    protected function writeProgress(string $msg, int $val, int $total): void
    {
        $this->logger->debug($msg . ' ' . $val . ' / ' . $total);
    }

}
