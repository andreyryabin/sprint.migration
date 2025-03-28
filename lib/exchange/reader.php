<?php

namespace Sprint\Migration\Exchange;

use CFile;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use XMLReader;

class Reader
{
    private string $file;

    /**
     * @throws MigrationException
     */
    public function __construct(string $file)
    {
        $this->file = $file;

        if (!class_exists('XMLReader')) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_EXCHANGE_DISABLED_XML'
                )
            );
        }
        if (!is_file($this->file)) {
            throw new MigrationException(
                Locale::getMessage('ERR_EXCHANGE_FILE_NOT_FOUND', ['#FILE#' => $this->file])
            );
        }
    }

    public function getRecordsCount(): int
    {
        $reader = new XMLReader();
        $reader->open($this->file);

        $total = 0;
        while ($reader->read()) {
            if ($this->isOpenTag($reader, 'item')) {
                $total++;
            }
        }

        $reader->close();
        return $total;
    }

    public function readRecords(int $offset, int $limit): array
    {
        $reader = new XMLReader();
        $reader->open($this->file);
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

    /**
     * @throws HelperException
     */
    public function getAttributes(): array
    {
        $attrs = $this->readAttributes();

        if (!$attrs['exchangeVersion'] || $attrs['exchangeVersion'] < Module::EXCHANGE_VERSION) {
            throw new HelperException(
                Locale::getMessage('ERR_EXCHANGE_VERSION', ['#NAME#' => $this->file])
            );
        }

        return $attrs;
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

        return $record;
    }

    private function readRecordField(XMLReader $reader, string $tagName): array
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

    private function getExchangeDir(): string
    {
        return dirname($this->file);
    }

    private function isOpenTag(XMLReader $reader, $tag): bool
    {
        return (
            $reader->nodeType == XMLReader::ELEMENT
            && $reader->name == $tag
            && !$reader->isEmptyElement
        );
    }

    private function isCloseTag(XMLReader $reader, $tag): bool
    {
        return (
            $reader->nodeType == XMLReader::END_ELEMENT
            && $reader->name == $tag
        );
    }

    private function getTagAttributes(XMLReader $reader): array
    {
        $attrs = [];
        if ($reader->hasAttributes) {
            while ($reader->moveToNextAttribute()) {
                $attrs[$reader->name] = $reader->value;
            }
        }
        return $attrs;
    }

    private function readAttributes(): array
    {
        $reader = new XMLReader();
        $reader->open($this->file);

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
