<?php

namespace Sprint\Migration\Exchange\Base;

use Sprint\Migration\AbstractBuilder;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\Out;
use XMLWriter;

class ExchangeWriter
{
    private AbstractBuilder $builderEntity;

    private int $limit = 10;
    private bool $copyFiles = true;

    private string $exchangeFile = '';

    /**
     * @throws MigrationException
     */
    public function __construct(AbstractBuilder $builderEntity)
    {
        $this->builderEntity = $builderEntity;

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
    public function execute(callable $converter): void
    {
        $params = $this->builderEntity->getRestartParams();
        if (!isset($params['offset'])) {
            $params['offset'] = 0;

            $this->createExchangeFile();
        }

        $dto = $converter($params['offset'], $this->getLimit());

        if (!$dto instanceof ExchangeDto) {
            throw new MigrationException('converter must return an instance of ExchangeDto');
        }

        $this->appendDtoToExchangeFile($dto);

        $params['offset'] += $dto->countChilds();

        Out::outProgress('', $params['offset'],1);

        if ($dto->countChilds() >= $this->getLimit()) {
            $this->builderEntity->setRestartParams($params);
            $this->builderEntity->restart();
        }

        $this->closeExchangeFile();

        unset($params['offset']);
        $this->builderEntity->setRestartParams($params);
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

    public function setExchangeFile(string $exchangeFile): static
    {
        $this->exchangeFile = $exchangeFile;
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

    protected function appendToExchangeFile($content): void
    {
        file_put_contents($this->exchangeFile, $content, FILE_APPEND);
    }

    private function getExchangeDir(): string
    {
        return dirname($this->exchangeFile);
    }
}
