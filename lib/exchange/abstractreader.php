<?php

namespace Sprint\Migration\Exchange;

use CFile;
use Sprint\Migration\AbstractExchange;
use XMLReader;

abstract class AbstractReader extends AbstractExchange
{
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
