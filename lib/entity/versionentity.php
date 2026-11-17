<?php

namespace Sprint\Migration\Entity;

use Sprint\Migration\Enum\VersionEnum;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Locale;
use Sprint\Migration\Version;

class VersionEntity
{

    /**
     * @throws MigrationException
     */
    public function __construct(
        private readonly ?VersionFile     $file = null,
        private readonly ?VersionRecord   $record = null,
        private readonly ?VersionExchange $exchange = null,
    )
    {
        if ($file === null && $record === null) {
            throw new MigrationException('Version not exists');
        }
    }


    public function hasFile(): bool
    {
        return $this->file !== null;
    }

    public function hasRecord(): bool
    {
        return $this->record !== null;
    }

    public function getVersionName(): string
    {
        if ($this->file) {
            return $this->file->getVersionName();
        }
        return $this->record->getVersionName();
    }

    public function getStatus(): string
    {
        return match (true) {
            $this->record && $this->file => VersionEnum::STATUS_INSTALLED,
            !$this->record && $this->file => VersionEnum::STATUS_NEW,
            $this->record && !$this->file => VersionEnum::STATUS_UNKNOWN,
        };
    }

    public function isModified(): bool
    {
        return (
            $this->record &&
            $this->file &&
            $this->record->getRecordHash() !== $this->file->getContentHash()
        );
    }

    public function getOlderVersion(): string
    {
        if ($this->file && $this->file->getOlderVersion()) {
            return $this->file->getOlderVersion();
        }
        return '';
    }

    /**
     * @throws MigrationException
     */
    public function getReleaseTag(): string
    {
        return $this->getRecord()->getReleaseTag();
    }

    /**
     * @throws MigrationException
     */
    public function getRecordStatus(): string
    {
        return $this->humanStatus(
            Locale::getMessage('RECORD_STATUS'),
            $this->getRecord()->getCreatedAt(),
            $this->getRecord()->getCreatedBy()
        );
    }

    /**
     * @throws MigrationException
     */
    public function getFileStatus(): string
    {
        return $this->humanStatus(
            Locale::getMessage('FILE_STATUS'),
            $this->getFile()->getCreatedAt(),
            $this->getFile()->getCreatedBy()
        );
    }

    /**
     * @throws MigrationException
     */
    public function getReleaseMessage(): string
    {
        $tag = $this->getReleaseTag();
        return $tag ? Locale::getMessage('RELEASE_TAG', ['#TAG#' => $tag]) : '';
    }

    public function getLabels(): string
    {
        $labels = [];

        if ($older = $this->getOlderVersion()) {
            $labels[] = Locale::getMessage('LABEL_OLDER', ['#V1#' => $older]);
        }

        if ($this->isModified()) {
            $labels[] = Locale::getMessage('LABEL_MODIFIED');
        }

        if ($this->getStatus() === VersionEnum::STATUS_UNKNOWN) {
            $labels[] = Locale::getMessage('LABEL_UNKNOWN');
        }

        if ($this->hasExchangeDir()) {
            $labels[] = Locale::getMessage('LABEL_EXCHANGE');
        }

        return implode(' ', $labels);
    }

    private function humanStatus(string $prefix, string $at, string $by): string
    {
        $by = $by ? '(' . $by . ')' : '';

        if ($at && $by) {
            return $prefix . ': ' . $at . ' ' . $by;
        } elseif ($at || $by) {
            return $prefix . ': ' . $at . $by;
        }
        return $prefix;
    }

    /**
     * @throws MigrationException
     */
    private function getRecord(): VersionRecord
    {
        if ($this->record !== null) {
            return $this->record;
        }
        throw new MigrationException('Version record not exists');
    }

    /**
     * @throws MigrationException
     */
    private function getFile(): VersionFile
    {
        if ($this->file !== null) {
            return $this->file;
        }
        throw new MigrationException('Version file not exists');
    }

    /**
     * @throws MigrationException
     */
    public function getFileLocation(): string
    {
        return $this->getFile()->getLocation();
    }

    /**
     * @throws MigrationException
     */
    public function getFileDescription(): string
    {
        return $this->getFile()->getDescription();
    }

    /**
     * @throws MigrationException
     */
    public function createClassObject(): Version
    {
        $class = $this->getFile()->getVersionClass();
        return new $class;
    }

    public function getExchangeDir(): string
    {
        return $this->exchange->getDir();
    }

    public function hasExchangeDir(): bool
    {
        return $this->exchange->hasDir();
    }


}