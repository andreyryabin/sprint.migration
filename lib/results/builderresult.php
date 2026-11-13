<?php

namespace Sprint\Migration\Results;

use Throwable;

class BuilderResult
{
    private bool $isRestart = false;
    private bool $isRebuild = false;
    private array $restartParams = [];
    private ?Throwable $lastException = null;

    public function setRestart(array $restartParams = []): static
    {
        $this->isRestart = true;
        $this->isRebuild = false;
        $this->restartParams = $restartParams;

        return $this;
    }

    public function setRebuild(array $restartParams = []): static
    {
        $this->isRebuild = true;
        $this->isRestart = false;
        $this->restartParams = $restartParams;

        return $this;
    }

    public function setException(Throwable $e): static
    {
        $this->isRestart = false;
        $this->lastException = $e;
        return $this;
    }

    public function isSuccess(): bool
    {
        return !$this->lastException
            && !$this->isRestart
            && !$this->isRebuild;
    }

    public function needRestart(): bool
    {
        return $this->isRestart;
    }

    public function needRebuild(): bool
    {
        return $this->isRebuild;
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