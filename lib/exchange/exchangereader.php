<?php

namespace Sprint\Migration\Exchange;

use CFile;
use Closure;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Interfaces\Restartable;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\Out;
use XMLReader;

class ExchangeReader
{
    private Restartable $restartable;
    private int $limit = 10;
    private string $exchangeFile = '';
    private Closure $recordConverter;

    /**
     * @throws MigrationException
     */
    public function __construct(Restartable $restartable, Closure $recordConverter)
    {
        $this->restartable = $restartable;

        $this->recordConverter = $recordConverter;

        if (!class_exists('XMLReader')) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_EXCHANGE_DISABLED_XML'
                )
            );
        }
    }

    /**
     * @throws HelperException
     * @throws RestartException
     */
    public function execute(Closure $converter): void
    {
        $this->checkExchangeFile();

        $attrs = $this->restartable->restartOnce('step1', fn() => $this->getExchangeAttributes());

        $this->checkExchangeAttributes($attrs);

        $this->restartable->restartWhile('step2', function ($offset) use (
            $converter,
            $attrs
        ) {

            $totalCount = $this->restartable->restartOnce('step2_1', fn() => $this->getExchangeRecordsCount());

            $records = $this->readRecords($offset, $this->getLimit());

            array_map(fn($record) => $converter(($this->recordConverter)($attrs, $record)), $records);

            $countRecords = count($records);

            $offset += $countRecords;

            Out::outProgress('Progress: ', $offset, $totalCount);

            return ($countRecords >= $this->getLimit()) ? $offset : false;
        });
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): ExchangeReader
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

        return $record;
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

    public function setExchangeFile(string $exchangeFile): ExchangeReader
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
    public function setExchangeResource(): ExchangeReader
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
    }

    /**
     * @throws HelperException
     */
    private function checkExchangeAttributes(array $attributes): void
    {
        if (!$attributes['exchangeVersion'] || $attributes['exchangeVersion'] < Module::EXCHANGE_VERSION) {
            throw new HelperException(
                Locale::getMessage('ERR_EXCHANGE_VERSION', ['#NAME#' => $this->exchangeFile])
            );
        }
    }

    private function getExchangeAttributes(): array
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

    private function getExchangeRecordsCount(): int
    {
        $reader = new XMLReader();
        $reader->open($this->exchangeFile);

        $total = 0;
        while ($reader->read()) {
            if ($this->isOpenTag($reader, 'item')) {
                $total++;
            }
        }

        $reader->close();
        return $total;
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
