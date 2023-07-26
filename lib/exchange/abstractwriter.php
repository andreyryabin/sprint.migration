<?php

namespace Sprint\Migration\Exchange;

use CFile;
use Exception;
use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Module;
use XMLWriter;

abstract class AbstractWriter extends AbstractExchange
{
    protected $copyFiles = true;

    public function setCopyFiles($copyFiles)
    {
        $this->copyFiles = (bool)$copyFiles;
        return $this;
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
                $val = $this->purifyValue($val);
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
