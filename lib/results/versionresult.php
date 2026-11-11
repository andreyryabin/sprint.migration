<?php

namespace Sprint\Migration\Results;

use Throwable;

class VersionResult
{
    private bool $isSuccess = true;
    private bool $isRestart = false;
    private array $restartParams = [];
    private ?Throwable $lastException = null;

    public function setRestart(array $restartParams = []): static
    {
        $this->isSuccess = false;
        $this->isRestart = true;
        $this->restartParams = $restartParams;

        return $this;
    }

    public function setException(Throwable $e): static
    {
        $this->isSuccess = false;
        $this->lastException = $e;
        return $this;
    }

    public function isSuccess(): bool
    {
        return $this->isSuccess;
    }

    public function needRestart(): bool
    {
        return $this->isRestart;
    }

    public function getRestartParams(): array
    {
        return $this->restartParams;
    }

    public function getLastException(): ?Throwable
    {
        return $this->lastException;
    }
}