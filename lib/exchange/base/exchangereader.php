<?php

namespace Sprint\Migration\Exchange\Base;

use CFile;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\ExchangeEntity;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\Traits\HelperManagerTrait;
use Sprint\Migration\Traits\OutTrait;
use XMLReader;

abstract class ExchangeReader
{
    use HelperManagerTrait;
    use OutTrait;

    protected ExchangeEntity $exchangeEntity;
    private string $file;
    protected int $limit = 10;


    /**
     * @throws MigrationException
     */
    public function __construct(ExchangeEntity $exchangeEntity)
    {
        $this->exchangeEntity = $exchangeEntity;

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
        $context = $this->exchangeEntity->getRestartParams();

        if (!isset($context['offset'])) {
            $this->checkExchangeFile();

            $context['offset'] = 0;
        }

        $records = $this->readRecords(
            (int)$context['offset'],
            $this->getLimit()
        );

        array_map(fn($record) => $converter($record), $records);

        $countRecords = count($records);
        $context['offset'] += $countRecords;

        $this->out('Progress: ', $context['offset']);

        if ($countRecords >= $this->getLimit()) {
            $this->exchangeEntity->setRestartParams($context);
            $this->exchangeEntity->restart();
        }

        unset($context['offset']);
        $this->exchangeEntity->setRestartParams($context);
    }

    public function setExchangeFile(string $file): static
    {
        $this->file = $file;
        return $this;
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

    protected function getExchangeDir(): string
    {
        return dirname($this->file);
    }

    protected function getExchangeFile(): string
    {
        return $this->file;
    }

    protected function readRecords(int $offset, int $limit): array
    {
        $reader = new XMLReader();
        $reader->open($this->getExchangeFile());
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

    public function setExchangeResource(string $name): static
    {
        $path = $this->exchangeEntity->getVersionConfig()->getVal('exchange_dir');

        $shortName = $this->exchangeEntity->getClassName();

        $this->setExchangeFile($path . '/' . $shortName . '_files/' . $name);

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
        if (!is_file($this->getExchangeFile())) {
            throw new HelperException(
                Locale::getMessage('ERR_EXCHANGE_FILE_NOT_FOUND', ['#FILE#' => $this->getExchangeFile()])
            );
        }

        $attributes = $this->getExchangeFileAttributes();

        if (!$attributes['exchangeVersion'] || $attributes['exchangeVersion'] < Module::getExchangeVersion()) {
            throw new HelperException(
                Locale::getMessage('ERR_EXCHANGE_VERSION', ['#NAME#' => $this->getExchangeFile()])
            );
        }

    }


    private function getExchangeFileAttributes(): array
    {

        $reader = new XMLReader();
        $reader->open($this->getExchangeFile());

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
