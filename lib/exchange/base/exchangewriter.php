<?php

namespace Sprint\Migration\Exchange\Base;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\ExchangeEntity;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\Traits\HelperManagerTrait;
use Sprint\Migration\Traits\OutTrait;
use XMLWriter;

abstract class ExchangeWriter
{
    use HelperManagerTrait;
    use OutTrait;

    protected ExchangeEntity $exchangeEntity;
    protected string $file;
    protected int $limit = 10;
    protected bool $copyFiles = true;

    abstract protected function getRecordsDto(int $offset, int $limit): ExchangeDto;

    /**
     * @throws MigrationException
     */
    public function __construct(ExchangeEntity $exchangeEntity)
    {
        $this->exchangeEntity = $exchangeEntity;

        if (!class_exists('XMLWriter')) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_EXCHANGE_DISABLED_XML'
                )
            );
        }
    }

    /**
     * @throws RestartException
     * @throws MigrationException
     */
    public function execute(): void
    {
        $params = $this->exchangeEntity->getRestartParams();
        if (!isset($params['offset'])) {
            $params['offset'] = 0;

            $this->createExchangeFile();
        }

        $dto = $this->getRecordsDto($params['offset'], $this->getLimit());

        $this->appendDtoToExchangeFile($dto);

        $params['offset'] += $dto->countChilds();

        $this->out('Progress: ', $params['offset']);

        if ($dto->countChilds() >= $this->getLimit()) {
            $this->exchangeEntity->setRestartParams($params);
            $this->exchangeEntity->restart();
        }

        $this->closeExchangeFile();

        unset($params['offset']);
        $this->exchangeEntity->setRestartParams($params);
    }

    public function setCopyFiles($copyFiles): static
    {
        $this->copyFiles = (bool)$copyFiles;
        return $this;
    }

    /**
     * @throws MigrationException
     */
    protected function createExchangeFile(array $attrs = []): void
    {
        $attrs['exchangeVersion'] = Module::getExchangeVersion();

        $str = '';
        foreach ($attrs as $attr => $value) {
            $str .= $attr . '="' . $value . '" ';
        }

        Module::createDir($this->getExchangeDir());

        $this->appendToExchangeFile('<?xml version="1.0" encoding="UTF-8"?>');
        $this->appendToExchangeFile('<items ' . $str . '>');
    }

    protected function closeExchangeFile(): void
    {
        $this->appendToExchangeFile('</items>');
    }

    /**
     * @throws MigrationException
     */
    protected function appendDtoToExchangeFile(ExchangeDto $dto): void
    {
        $writer = new XMLWriter();
        $writer->openMemory();

        /** @var ExchangeDto $child */
        foreach ($dto->getChilds() as $child) {
            $this->appendDtoToWriter($writer, $child);
        }

        $this->appendToExchangeFile($writer->flush());

        $this->copyDtoFiles($dto);
    }

    /**
     * @throws MigrationException
     */
    protected function copyDtoFiles(ExchangeDto $dto): void
    {
        if ($this->copyFiles) {
            foreach ($dto->getFiles() as $file) {
                $filePath = Module::getDocRoot() . $file['SRC'];
                if (file_exists($filePath)) {
                    $newPath = $this->getExchangeDir() . '/' . $file['SUBDIR'] . '/' . $file['FILE_NAME'];
                    Module::createDir(dirname($newPath));
                    copy($filePath, $newPath);
                }
            }
        }
    }

    private function appendDtoToWriter(XMLWriter $writer, ExchangeDto $dto): void
    {
        $writer->startElement($dto->getName());

        foreach ($dto->getAttributes() as $atname => $atval) {
            $writer->writeAttribute($atname, $atval);
        }
        /** @var ExchangeDto $child */
        foreach ($dto->getChilds() as $child) {
            $this->appendDtoToWriter($writer, $child);
        }

        if ($dto->getText()) {
            $writer->text($dto->getText());
            //$writer->writeCdata($dto->getText());
        }

        $writer->endElement();
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

    public function setLimit($limit): static
    {
        $this->limit = $limit;
        return $this;
    }

    protected function getExchangeDir(): string
    {
        return dirname($this->file);
    }

    protected function appendToExchangeFile($content): void
    {
        file_put_contents($this->file, $content, FILE_APPEND);
    }
}
