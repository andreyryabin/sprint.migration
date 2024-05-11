<?php

namespace Sprint\Migration;

use CFile;
use Exception;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Traits\HelperManagerTrait;
use XMLReader;
use XMLWriter;

abstract class AbstractExchange
{
    const EXCHANGE_VERSION = 2;
    use HelperManagerTrait;
    use OutTrait;

    protected $exchangeEntity;
    protected $file;
    protected $limit = 10;

    /**
     * abstractexchange constructor.
     *
     * @param ExchangeEntity $exchangeEntity
     *
     * @throws MigrationException
     */
    public function __construct(ExchangeEntity $exchangeEntity)
    {
        $this->exchangeEntity = $exchangeEntity;

        if (!class_exists('XMLReader') || !class_exists('XMLWriter')) {
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

    /**
     * @param $name
     *
     * @return $this
     */
    public function setExchangeResource($name)
    {
        $this->setExchangeFile(
            $this->exchangeEntity->getVersionConfig()->getVal('exchange_dir') . '/' .
            $this->exchangeEntity->getClassName() . '_files/' .
            $name
        );
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

    /**
     * @throws RestartException
     */
    protected function restart()
    {
        throw new RestartException();
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
            $filePath = Module::getDocRoot() . $file['SRC'];
            if (file_exists($filePath)) {
                $newPath = $this->getExchangeDir() . '/' . $file['SUBDIR'] . '/' . $file['FILE_NAME'];
                Module::createDir(dirname($newPath));
                if (copy($filePath, $newPath)) {
                    $this->writeValue(
                        $writer,
                        $file['SUBDIR'] . '/' . $file['FILE_NAME'],
                        [
                            'name'        => $file['ORIGINAL_NAME'],
                            'description' => $file['DESCRIPTION'],
                        ]
                    );
                }
            }
        }
    }

    protected function makeFileValue($value)
    {
        if (!empty($value['value'])) {
            $path = $this->getExchangeDir() . '/' . $value['value'];
            $file = CFile::MakeFileArray($path);
            if (!empty($value['name'])) {
                $file['name'] = $value['name'];
            }
            if (!empty($value['description'])) {
                $file['description'] = $value['description'];
            }
            return $file;
        }
        return false;
    }

    protected function collectField(XMLReader $reader, $tag)
    {
        $field = [];
        if ($this->isOpenTag($reader, $tag)) {
            if ($reader->hasAttributes) {
                while ($reader->moveToNextAttribute()) {
                    $field[$reader->name] = $this->purifyValue($reader->value);
                }
            }
            $field['value'] = [];
            do {
                $reader->read();
                if ($this->isOpenTag($reader, 'value')) {
                    $val = [];
                    if ($reader->hasAttributes) {
                        while ($reader->moveToNextAttribute()) {
                            $val[$reader->name] = $this->purifyValue($reader->value);
                        }
                    }
                    $reader->read();
                    if (isset($val['type']) && $val['type'] == 'json') {
                        $val['value'] = json_decode($reader->value, true);
                    } else {
                        $val['value'] = $this->purifyValue($reader->value);
                    }
                    $field['value'][] = $val;
                }
            } while (!$this->isCloseTag($reader, $tag));
        }
        return $field;
    }

    protected function purifyValue($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->purifyValue($value);
            }
        } else {
            $data = htmlspecialchars_decode($data);
        }
        return $data;
    }

    protected function isOpenTag(XMLReader $reader, $tag)
    {
        return (
            $reader->nodeType == XMLReader::ELEMENT
            && $reader->name == $tag
            && !$reader->isEmptyElement
        );
    }

    protected function isCloseTag(XMLReader $reader, $tag)
    {
        return (
            $reader->nodeType == XMLReader::END_ELEMENT
            && $reader->name == $tag
        );
    }

    protected function getExchangeDir()
    {
        return dirname($this->file);
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

    protected function getExchangeFile()
    {
        return $this->file;
    }
}
