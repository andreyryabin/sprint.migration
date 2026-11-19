<?php

namespace Sprint\Migration\Results;

use Throwable;

class BuilderResult
{
    private bool $isRestart = false;
    private bool $isRebuild = false;
    private ?Throwable $lastException = null;

    public function setRestart(): static
    {
        $this->isRestart = true;
        $this->isRebuild = false;

        return $this;
    }

    public function setRebuild(): static
    {
        $this->isRebuild = true;
        $this->isRestart = false;

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

    public function getException(): ?Throwable
    {
        return $this->lastException;
    }

}