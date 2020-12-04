<?php

namespace Sprint\Migration;

use CFile;
use Exception;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\RestartException;
use Sprint\Migration\Exchange\Helpers\ExchangeHelper;
use XMLReader;
use XMLWriter;

abstract class AbstractExchange
{
    use OutTrait {
        out as protected;
        outIf as protected;
        outProgress as protected;
        outNotice as protected;
        outNoticeIf as protected;
        outInfo as protected;
        outInfoIf as protected;
        outSuccess as protected;
        outSuccessIf as protected;
        outWarning as protected;
        outWarningIf as protected;
        outError as protected;
        outErrorIf as protected;
        outDiff as protected;
        outDiffIf as protected;
    }

    protected $exchangeEntity;
    protected $exchangeHelper;
    protected $file;
    protected $limit = 10;

    /**
     * abstractexchange constructor.
     *
     * @param ExchangeEntity $exchangeEntity
     * @param ExchangeHelper $exchangeHelper
     *
     * @throws ExchangeException
     */
    public function __construct(
        ExchangeEntity $exchangeEntity,
        ExchangeHelper $exchangeHelper
    ) {
        $this->exchangeEntity = $exchangeEntity;
        $this->exchangeHelper = $exchangeHelper;

        $enabled = (
            class_exists('XMLReader')
            && class_exists('XMLWriter')
            && $this->exchangeHelper->isEnabled()
        );

        if (!$enabled) {
            throw new ExchangeException(
                Locale::getMessage(
                    'ERR_EXCHANGE_DISABLED'
                )
            );
        }
    }

    public function setExchangeFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @param $name
     *
     * @throws ExchangeException
     * @return $this
     */
    public function setExchangeResource($name)
    {
        $this->setExchangeFile(
            $this->exchangeEntity->getResourceFile($name)
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

    protected function writeSerializedValue($writer, $value)
    {
        if (is_array($value)) {
            $this->writeSingleValue($writer, serialize($value), ['type' => 'serialized']);
        } else {
            $this->writeSingleValue($writer, $value);
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
                        $writer, $file['SUBDIR'] . '/' . $file['FILE_NAME'], [
                            'description' => $file['DESCRIPTION'],
                        ]
                    );
                }
            }
        }
    }

    protected function makeFile($value)
    {
        if (!empty($value['value'])) {
            $path = $this->getExchangeDir() . '/' . $value['value'];
            $file = CFile::MakeFileArray($path);
            if (!empty($value['description'])) {
                $file['description'] = $value['description'];
            }
            return $file;
        }

        return false;
    }

    protected function makeValue($value)
    {
        $type = isset($value['type']) ? $value['type'] : '';
        if ($type == 'serialized') {
            return unserialize($value['value']);
        }
        return $value['value'];
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
                    $val['value'] = $this->purifyValue($reader->value);

                    $field['value'][] = $val;
                }
            } while (!$this->isCloseTag($reader, $tag));
        }
        return $field;
    }

    protected function purifyValue($value)
    {
        $value = trim($value);

        $search = ["'&(quot|#34);'i", "'&(lt|#60);'i", "'&(gt|#62);'i", "'&(amp|#38);'i"];
        $replace = ["\"", "<", ">", "&"];

        if (preg_match("/^\s*$/", $value)) {
            $res = '';
        } elseif (strpos($value, "&") === false) {
            $res = $value;
        } else {
            $res = preg_replace($search, $replace, $value);
        }

        return $res;
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
