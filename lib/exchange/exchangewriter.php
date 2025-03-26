<?php

namespace Sprint\Migration\Exchange;

use Closure;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Interfaces\Restartable;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use XMLWriter;

class ExchangeWriter
{
    private bool $copyFiles = true;

    private string $exchangeFile = '';

    /**
     * @throws MigrationException
     */
    public function __construct()
    {
        if (!class_exists('XMLWriter')) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_EXCHANGE_DISABLED_XML'
                )
            );
        }
    }

    public function setCopyFiles(bool $copyFiles): ExchangeWriter
    {
        $this->copyFiles = $copyFiles;
        return $this;
    }

    /**
     * @throws MigrationException
     */
    public function createExchangeFile(array $attributes): void
    {
        Module::createDir($this->getExchangeDir());

        $this->appendToExchangeFile('<?xml version="1.0" encoding="UTF-8"?>');
        $this->appendToExchangeFile('<items ' . $this->makeAtts($attributes) . '>');
    }

    public function closeExchangeFile(): void
    {
        $this->appendToExchangeFile('</items>');
    }

    /**
     * @throws MigrationException
     */
    public function appendTagsToExchangeFile(ExchangeTag $tag): void
    {
        $writer = new XMLWriter();
        $writer->openMemory();

        /** @var ExchangeTag $child */
        foreach ($tag->getChilds() as $child) {
            $this->appendTagsToWriter($writer, $child);
        }

        $this->appendToExchangeFile($writer->flush());

        $this->copyTagsFiles($tag);
    }

    /**
     * @throws MigrationException
     */
    protected function copyTagsFiles(ExchangeTag $tag): void
    {
        if ($this->copyFiles) {
            foreach ($tag->getFiles() as $file) {
                $filePath = Module::getDocRoot() . $file['SRC'];
                if (file_exists($filePath)) {
                    $newPath = $this->getExchangeDir() . '/' . $file['SUBDIR'] . '/' . $file['FILE_NAME'];
                    Module::createDir(dirname($newPath));
                    copy($filePath, $newPath);
                }
            }
        }
    }

    private function appendTagsToWriter(XMLWriter $writer, ExchangeTag $tag): void
    {
        $writer->startElement($tag->getName());

        foreach ($tag->getAttributes() as $atname => $atval) {
            $writer->writeAttribute($atname, $atval);
        }
        /** @var ExchangeTag $child */
        foreach ($tag->getChilds() as $child) {
            $this->appendTagsToWriter($writer, $child);
        }

        if ($tag->getText()) {
            $writer->text($tag->getText());
            //$writer->writeCdata($tag->getText());
        }

        $writer->endElement();
    }

    public function setExchangeFile(string $exchangeFile): ExchangeWriter
    {
        $this->exchangeFile = $exchangeFile;
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

    private function makeAtts(array $attrs): string
    {
        $attrs['exchangeVersion'] = Module::EXCHANGE_VERSION;

        $str = '';
        foreach ($attrs as $k => $v) {
            $str .= $k . '="' . $v . '" ';
        }
        return $str;
    }

    public function execute(
        Restartable $restartable,
        Closure     $recordsCntFn,
        Closure     $recordsFn,
        Closure     $fileAttrsFn,
        Closure     $progressFn,
        int         $limit = 20
    ): void
    {
        $totalCount = $restartable->restartOnce('init1', $recordsCntFn);

        $restartable->restartOnce('init2', fn() => $this->createExchangeFile($fileAttrsFn()));

        $restartable->restartWithOffset('init3', function (int $offset) use ($recordsFn, $progressFn, $limit, $totalCount) {

            $tags = $recordsFn($offset, $limit);

            $this->appendTagsToExchangeFile($tags);

            $progressFn('Progress: ', $offset, $totalCount);

            return ($tags->countChilds() >= $limit) ? $offset + $tags->countChilds() : false;
        });

        $restartable->restartOnce('init4', fn() => $this->closeExchangeFile());
    }
}
