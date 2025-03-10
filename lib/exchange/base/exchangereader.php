<?php

namespace Sprint\Migration\Exchange\Base;

use CFile;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\Out;
use Sprint\Migration\Version;
use XMLReader;

abstract class ExchangeReader
{
    private Version $versionEntity;
    private int $limit = 10;
    private string $exchangeFile = '';


    /**
     * @throws MigrationException
     */
    public function __construct(Version $versionEntity)
    {
        $this->versionEntity = $versionEntity;

        if (!class_exists('XMLReader')) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_EXCHANGE_DISABLED_XML'
                )
            );
        }
    }

    abstract protected function convertRecord(array $record): array;

    /**
     * @param callable $converter
     *
     * @throws HelperException
     * @throws RestartException
     */
    public function execute(callable $converter): void
    {
        $params = $this->versionEntity->getRestartParams();

        if (!isset($params['offset'])) {
            $this->checkExchangeFile();

            $params['offset'] = 0;
        }

        $records = $this->readRecords(
            (int)$params['offset'],
            $this->getLimit()
        );

        array_map(fn($record) => $converter($record), $records);

        $countRecords = count($records);
        $params['offset'] += $countRecords;

        Out::outProgress('',$params['offset'],1);

        if ($countRecords >= $this->getLimit()) {
            $this->versionEntity->setRestartParams($params);
            $this->versionEntity->restart();
        }

        unset($params['offset']);
        $this->versionEntity->setRestartParams($params);
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    protected function readRecords(int $offset, int $limit): array
    {
        $reader = new XMLReader();
        $reader->open($this->exchangeFile);
        $index = 0;

        $records = [];

        while ($reader->read()) {
            if ($this->isOpenTag($reader, 'item')) {
                if ($index >= $offset && $index < $offset + $limit) {
                    $records[] = $this->readRecord($reader);
                } elseif ($index > $offset + $limit) {
                    break;
                }
                $index++;
            }
        }

        $reader->close();

        return $records;
    }

    private function readRecord(XMLReader $reader): array
    {
        $record = $this->getTagAttributes($reader);
        $record['fields'] = [];
        $record['properties'] = [];

        do {
            $reader->read();
            if ($this->isOpenTag($reader, 'field')) {
                $record['fields'][] = $this->readRecordField($reader, 'field');
            }
            if ($this->isOpenTag($reader, 'property')) {
                $record['properties'][] = $this->readRecordField($reader, 'property');
            }
        } while (!$this->isCloseTag($reader, 'item'));

        return $this->convertRecord($record);

    }


    protected function readRecordField(XMLReader $reader, string $tagName): array
    {
        $field = $this->getTagAttributes($reader);
        $field['value'] = [];

        do {
            $reader->read();
            if ($this->isOpenTag($reader, 'value')) {
                $val = $this->getTagAttributes($reader);
                $reader->read();
                if (isset($val['type']) && $val['type'] == 'json') {
                    $val['value'] = json_decode($reader->value, true);
                } elseif (isset($val['type']) && $val['type'] == 'file') {
                    $val['value'] = $this->makeFileValue($reader->value, $val);
                } else {
                    $val['value'] = $reader->value;
                }
                $field['value'][] = $val;
            }
        } while (!$this->isCloseTag($reader, $tagName));

        return $field;
    }

    public function setExchangeFile(string $exchangeFile): static
    {
        $this->exchangeFile = $exchangeFile;
        return $this;
    }

    private function getExchangeDir(): string
    {
        return dirname($this->exchangeFile);
    }

    /**
     * @deprecated
     */
    public function setExchangeResource(): static
    {
        return $this;
    }

    protected function isOpenTag(XMLReader $reader, $tag): bool
    {
        return (
            $reader->nodeType == XMLReader::ELEMENT
            && $reader->name == $tag
            && !$reader->isEmptyElement
        );
    }

    protected function isCloseTag(XMLReader $reader, $tag): bool
    {
        return (
            $reader->nodeType == XMLReader::END_ELEMENT
            && $reader->name == $tag
        );
    }

    protected function getTagAttributes(XMLReader $reader): array
    {
        $attrs = [];
        if ($reader->hasAttributes) {
            while ($reader->moveToNextAttribute()) {
                $attrs[$reader->name] = $reader->value;
            }
        }
        return $attrs;
    }

    /**
     * @throws HelperException
     */
    private function checkExchangeFile(): void
    {
        if (!is_file($this->exchangeFile)) {
            throw new HelperException(
                Locale::getMessage('ERR_EXCHANGE_FILE_NOT_FOUND', ['#FILE#' => $this->exchangeFile])
            );
        }

        $attributes = $this->getExchangeFileAttributes();

        if (!$attributes['exchangeVersion'] || $attributes['exchangeVersion'] < Module::getExchangeVersion()) {
            throw new HelperException(
                Locale::getMessage('ERR_EXCHANGE_VERSION', ['#NAME#' => $this->exchangeFile])
            );
        }

    }


    private function getExchangeFileAttributes(): array
    {

        $reader = new XMLReader();
        $reader->open($this->exchangeFile);

        $attributes = [];

        while ($reader->read()) {
            if ($this->isOpenTag($reader, 'items')) {
                $attributes = $this->getTagAttributes($reader);
                break;
            }
        }

        $reader->close();
        return $attributes;
    }


    private function makeFileValue($value, $attrs): false|array
    {
        if (!empty($value)) {
            $path = $this->getExchangeDir() . '/' . $value;
            $file = CFile::MakeFileArray($path);
            if (!empty($attrs['name'])) {
                $file['name'] = $attrs['name'];
            }
            if (!empty($attrs['description'])) {
                $file['description'] = $attrs['description'];
            }
            return $file;
        }
        return false;
    }
}
