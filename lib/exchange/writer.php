<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use XMLWriter;

class Writer
{
    private string $file;
    private bool $copyFiles;

    /**
     * @throws MigrationException
     */
    public function __construct(string $file)
    {
        $this->file = $file;

        if (!class_exists('XMLWriter')) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_EXCHANGE_DISABLED_XML'
                )
            );
        }
    }

    /**
     * @throws MigrationException
     */
    public function createFile(array $attributes): void
    {
        Module::createDir($this->getFileDir());

        $this->appendToFile('<?xml version="1.0" encoding="UTF-8"?>');
        $this->appendToFile('<items ' . $this->makeAttributes($attributes) . '>');
    }

    public function closeFile(): void
    {
        $this->appendToFile('</items>');
    }

    /**
     * @throws MigrationException
     */
    public function appendTagsToFile(WriterTag $tag): int
    {
        $writer = new XMLWriter();
        $writer->openMemory();
        $writer->setIndent(true);
        /** @var WriterTag $child */
        foreach ($tag->getChilds() as $child) {
            $this->appendTagsToXml($writer, $child);
        }

        $this->appendToFile($writer->flush());

        if ($this->copyFiles) {
            $this->copyTagsFiles($tag);
        }

        return $tag->countChilds();
    }

    /**
     * @throws MigrationException
     */
    private function copyTagsFiles(WriterTag $tag): void
    {
        foreach ($tag->getFiles() as $file) {
            $filePath = Module::getDocRoot() . $file['SRC'];
            if (file_exists($filePath)) {
                $newPath = $this->getFileDir() . '/' . $file['SUBDIR'] . '/' . $file['FILE_NAME'];
                Module::createDir(dirname($newPath));
                copy($filePath, $newPath);
            }
        }

    }

    private function appendTagsToXml(XMLWriter $writer, WriterTag $tag): void
    {
        $writer->startElement($tag->getName());

        foreach ($tag->getAttributes() as $atname => $atval) {
            $writer->writeAttribute($atname, $atval);
        }
        /** @var WriterTag $child */
        foreach ($tag->getChilds() as $child) {
            $this->appendTagsToXml($writer, $child);
        }

        if ($tag->getText()) {
            if ($tag->isCdata()) {
                $writer->writeCdata($tag->getText());
            } else {
                $writer->text($tag->getText());
            }
        }

        $writer->endElement();
    }

    private function appendToFile($content): void
    {
        file_put_contents($this->file, $content . PHP_EOL, FILE_APPEND);
    }

    private function getFileDir(): string
    {
        return dirname($this->file);
    }

    private function makeAttributes(array $attrs): string
    {
        $attrs['exchangeVersion'] = Module::EXCHANGE_VERSION;

        $str = '';
        foreach ($attrs as $k => $v) {
            $str .= $k . '="' . $v . '" ';
        }
        return $str;
    }

    public function setCopyFiles(bool $copyFiles): Writer
    {
        $this->copyFiles = $copyFiles;
        return $this;
    }

}
