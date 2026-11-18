<?php

namespace Sprint\Migration\Entity;

final class VersionExchange
{
    public function __construct(
        private readonly string $directoryPath,
        private readonly bool $directoryExists,
    ) {}

    public function getDir(): string
    {
        return $this->directoryPath;
    }

    public function hasDir(): bool
    {
        return $this->directoryExists;
    }

}