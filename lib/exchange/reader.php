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
    private string $path;

    /**
     * @throws MigrationException
     */
    public function __construct(string $file)
    {
        if (!class_exists('XMLReader')) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_EXCHANGE_DISABLED_XML'
                )
            );
        }
        if (!is_file($file)) {
            throw new MigrationException(
                Locale::getMessage('ERR_EXCHANGE_FILE_NOT_FOUND', ['#FILE#' => $file])
            );
        }

        $this->file = $file;
        $this->path = dirname($file);
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
                $field['value'][] = $this->readRecordValue($reader);
            }
        } while (!$this->isCloseTag($reader, $tagName));

        return $field;
    }

    private function readRecordValue(XMLReader $reader): array
    {
        $val = $this->getTagAttributes($reader);
        $reader->read();
        $text = $reader->value;

        if (isset($val['type']) && $val['type'] == 'json') {
            $val['value'] = $this->decodeJson($text);
        } elseif (isset($val['type']) && $val['type'] == 'file') {
            $val['value'] = $this->makeFileValue($text, $val);
        } elseif (isset($val['name']) && $this->isFile($text)) {
            $val['value'] = $this->makeFileValue($text, $val);
        } else {
            $val['value'] = htmlspecialchars_decode($text);
        }

        return $val;
    }

    private function decodeJson(string $json): array
    {
        return json_decode(trim($json), true);
    }

    private function isFile(string $value): bool
    {
        $value = trim($value);
        return $value && is_file($this->path . '/' . $value);
    }

    private function isOpenTag(XMLReader $reader, string $tagName): bool
    {
        return (
            $reader->nodeType == XMLReader::ELEMENT
            && $reader->name == $tagName
            && !$reader->isEmptyElement
        );
    }

    private function isCloseTag(XMLReader $reader, string $tagName): bool
    {
        return (
            $reader->nodeType == XMLReader::END_ELEMENT
            && $reader->name == $tagName
        );
    }

    private function getTagAttributes(XMLReader $reader): array
    {
        $attrs = [];
        if ($reader->hasAttributes) {
            while ($reader->moveToNextAttribute()) {
                $attrs[$reader->name] = htmlspecialchars_decode($reader->value);
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

    private function makeFileValue(string $value, array $attrs): false|array
    {
        $value = trim($value);
        if (empty($value)) {
            return false;
        }

        $file = CFile::MakeFileArray($this->path . '/' . $value);
        if (empty($file)) {
            return false;
        }

        if (!empty($attrs['name'])) {
            $file['name'] = $attrs['name'];
        }
        if (!empty($attrs['description'])) {
            $file['description'] = $attrs['description'];
        }
        return $file;
    }
}
