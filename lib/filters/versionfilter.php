<?php

namespace Sprint\Migration\Filters;

use Sprint\Migration\Entity\VersionEntity;
use Sprint\Migration\Enum\VersionEnum;

class VersionFilter
{
    public function __construct(
        private readonly string $status = '',
        private readonly string $tag = '',
        private readonly string $search = '',
        private readonly bool $modified = false,
        private readonly bool $older = false,
        private readonly bool $actual = false,
        private readonly string $actualTs = '',
        private readonly bool $sortDesc = false,
    ) {}

    public static function fromArray(array $filter, string $actualTs): self
    {
        $view = $filter['view'] ?? '';
        $sort = $filter['sort'] ?? '';

        $changes = match ($view) {
            'new'       => ['status' => VersionEnum::STATUS_NEW],
            'installed' => ['status' => VersionEnum::STATUS_INSTALLED],
            'unknown'   => ['status' => VersionEnum::STATUS_UNKNOWN],
            'tag'       => ['tag' => $filter['search'] ?? ''],
            'modified'  => ['modified' => true],
            'older'     => ['older' => true],
            'actual'    => ['actual' => true],
            default     => [],
        };

        $filter = array_merge($filter, $changes);

        return new self(
            status: (string) ($filter['status'] ?? ''),
            tag: (string) ($filter['tag'] ?? ''),
            search: (string) ($filter['search'] ?? ''),
            modified: (bool) ($filter['modified'] ?? false),
            older: (bool) ($filter['older'] ?? false),
            actual: (string) ($filter['actual'] ?? ''),
            actualTs: $actualTs,
            sortDesc: ($sort === VersionEnum::SORT_DESC),
        );
    }

    public function matches(VersionEntity $version): bool
    {
        return $this->matchesStatus($version)
            && $this->matchesTag($version)
            && $this->matchesSearch($version)
            && $this->matchesActual($version)
            && $this->matchesModified($version)
            && $this->matchesOlder($version);
    }

    private function matchesStatus(VersionEntity $v): bool
    {
        return $this->status === '' || $v->hasStatus($this->status);
    }

    private function matchesTag(VersionEntity $v): bool
    {
        return $this->tag === '' || $v->getReleaseTag() === $this->tag;
    }

    private function matchesSearch(VersionEntity $v): bool
    {
        return $this->search === ''
            || str_contains($v->getVersionName(), $this->search)
            || str_contains($v->getReleaseTag(), $this->search)
            || str_contains($v->getFileDescription() ?? '', $this->search);
    }

    private function matchesActual(VersionEntity $v): bool
    {
        return !($this->actual && $this->actualTs)
            || $v->hasStatus(VersionEnum::STATUS_NEW)
            || ($v->getVersionTimestamp() >= $this->actualTs);
    }

    private function matchesModified(VersionEntity $v): bool
    {
        return !$this->modified || $v->isModified();
    }

    private function matchesOlder(VersionEntity $v): bool
    {
        return !$this->older || $v->isOlder();
    }

    public function isSortDesc(): bool
    {
        return $this->sortDesc;
    }
}