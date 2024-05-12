<?php

namespace Sprint\Migration\Exchange;

use CFile;
use Exception;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\ExchangeEntity;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\Traits\HelperManagerTrait;
use Sprint\Migration\Traits\OutTrait;
use XMLWriter;

abstract class AbstractWriter
{
    use HelperManagerTrait;
    use OutTrait;

    protected $exchangeEntity;
    protected $file;
    protected $limit     = 10;
    protected $copyFiles = true;

    public function setCopyFiles($copyFiles)
    {
        $this->copyFiles = (bool)$copyFiles;
        return $this;
    }

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

        if (!$this->isEnabled()) {
            throw new MigrationException(
                Locale::getMessage(
                    'ERR_EXCHANGE_DISABLED'
                )
            );
        }
    }

    protected function isEnabled()
    {
        return true;
    }

    public function setExchangeFile($file)
    {
        $this->file = $file;
        return $this;
    }

    public function getLimit()
    {
        return $this->limit;
    }

    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    protected function getExchangeDir()
    {
        return dirname($this->file);
    }

    protected function getExchangeFile()
    {
        return $this->file;
    }

    protected function writeValue(XMLWriter $writer, $val, $attributes = [])
    {
        if (is_array($val)) {
            foreach ($val as $val1) {
                $this->writeSingleValue($writer, $val1, $attributes);
            }
        } else {
            $this->writeSingleValue($writer, $val, $attributes);
        }
    }

    protected function writeSingleValue(XMLWriter $writer, $val, $attributes = [])
    {
        if (!empty($val)) {
            if (is_array($val)) {
                $val = json_encode($val, JSON_UNESCAPED_UNICODE);
                $attributes['type'] = 'json';
            }
            $writer->startElement('value');
            foreach ($attributes as $atcode => $atval) {
                if (!empty($atval)) {
                    $writer->writeAttribute($atcode, $atval);
                }
            }
            $writer->text($val);
            $writer->endElement();
        }
    }

    /**
     * @param XMLWriter $writer
     * @param           $fileIds
     *
     * @throws Exception
     */
    protected function writeFile(XMLWriter $writer, $fileIds)
    {
        if (is_array($fileIds)) {
            foreach ($fileIds as $fileId) {
                $this->writeSingleFile($writer, $fileId);
            }
        } else {
            $this->writeSingleFile($writer, $fileIds);
        }
    }

    /**
     * @param XMLWriter $writer
     * @param           $fileId
     *
     * @throws Exception
     */
    protected function writeSingleFile(XMLWriter $writer, $fileId)
    {
        $file = CFile::GetFileArray($fileId);
        if (!empty($file)) {
            $this->writeValue(
                $writer,
                $file['SUBDIR'] . '/' . $file['FILE_NAME'],
                [
                    'name'        => $file['ORIGINAL_NAME'],
                    'description' => $file['DESCRIPTION'],
                ]
            );

            if ($this->copyFiles) {
                $filePath = Module::getDocRoot() . $file['SRC'];
                if (file_exists($filePath)) {
                    $newPath = $this->getExchangeDir() . '/' . $file['SUBDIR'] . '/' . $file['FILE_NAME'];
                    Module::createDir(dirname($newPath));
                    copy($filePath, $newPath);
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function createExchangeDir()
    {
        Module::createDir($this->getExchangeDir());
    }

    protected function appendToExchangeFile($content)
    {
        file_put_contents($this->file, $content, FILE_APPEND);
    }
}
