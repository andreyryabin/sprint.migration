<?php

namespace Sprint\Migration\Entity;

final class VersionFile
{

    public function __construct(
        private readonly string $versionName,
        private readonly string $location,
        private readonly string $versionClass,
        private readonly string $description,
        private readonly string $olderVersion,
        private readonly string $contentHash,
        private readonly string $createdAt,
        private readonly string $createdBy,
    )
    {
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): string
    {
        return $this->createdBy;
    }

    public function getOlderVersion(): string
    {
        return $this->olderVersion;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getContentHash(): string
    {
        return $this->contentHash;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function getVersionClass(): string
    {
        return $this->versionClass;
    }

    public function getVersionName(): string
    {
        return $this->versionName;
    }
}