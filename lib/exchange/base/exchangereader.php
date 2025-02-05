<?php

namespace Sprint\Migration\Exchange\Base;

use CFile;
use ReflectionClass;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\ExchangeEntity;
use Sprint\Migration\Locale;
use Sprint\Migration\Traits\HelperManagerTrait;
use Sprint\Migration\Traits\OutTrait;
use XMLReader;

abstract class ExchangeReader
{
    use HelperManagerTrait;
    use OutTrait;

    protected $exchangeEntity;
    protected $file;
    protected $limit = 10;

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

    /**
     * @param $name
     *
     * @return $this
     */
    public function setExchangeResource($name)
    {
        $path = $this->exchangeEntity->getVersionConfig()->getVal('exchange_dir');

        $shortName = (new ReflectionClass($this->exchangeEntity))->getShortName();

        $this->setExchangeFile($path . '/' . $shortName . '_files/' . $name);

        return $this;
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
                    $field[$reader->name] = $reader->value;
                }
            }
            $field['value'] = [];
            do {
                $reader->read();
                if ($this->isOpenTag($reader, 'value')) {
                    $val = [];
                    if ($reader->hasAttributes) {
                        while ($reader->moveToNextAttribute()) {
                            $val[$reader->name] = $reader->value;
                        }
                    }
                    $reader->read();
                    if (isset($val['type']) && $val['type'] == 'json') {
                        $val['value'] = json_decode($reader->value, true);
                    } else {
                        $val['value'] = $reader->value;
                    }
                    $field['value'][] = $val;
                }
            } while (!$this->isCloseTag($reader, $tag));
        }
        return $field;
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
}
