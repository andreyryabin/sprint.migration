<?php

namespace Sprint\Migration\Entity;

final class VersionRecord
{
    public function __construct(
        private readonly string $versionName,
        private readonly string $releaseTag,
        private readonly string $createdAt,
        private readonly string $createdBy,
        private readonly string $recordHash,
    )
    {
    }

    public function getReleaseTag(): string
    {
        return $this->releaseTag;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function getRecordHash(): string
    {
        return $this->recordHash;
    }

    public function getVersionName(): string
    {
        return $this->versionName;
    }
}