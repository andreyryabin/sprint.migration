<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\RestartException;
use XMLReader;

class IblockElementsImport extends AbstractExchange
{
    protected $file;


    public function from($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @param callable $callback
     * @throws RestartException
     */
    public function execute(callable $callback)
    {
        if (!isset($this->params['all'])) {
            $reader = new XMLReader();
            $reader->open($this->file);
            $this->params['all'] = 0;
            $this->params['pos'] = 0;

            while ($reader->read()) {
                if ($this->isOpenTag($reader, 'item')) {
                    $this->params['all']++;
                }
            }
        }

        $limit = 1;
        $index = 0;

        $reader = new XMLReader();
        $reader->open($this->file);

        while ($reader->read()) {
            if ($this->isOpenTag($reader, 'item')) {

                $collect = (
                    $index >= $this->params['pos'] &&
                    $index < $this->params['pos'] + $limit
                );

                $finish = ($index >= $this->params['all'] - 1);
                $restart = ($index >= $this->params['pos'] + $limit);

                if ($collect) {
                    $this->collectItem($reader, $callback);
                }

                if ($finish || $restart) {
                    $this->outProgress(
                        ($index + 1) . ':' . $this->params['all'],
                        ($index + 1),
                        $this->params['all']
                    );
                }

                if ($restart) {
                    $this->params['pos'] = $index;
                    $this->restart();
                }
                $index++;
            }
        }

        $reader->close();
        unset($this->params['NavPageCount']);
        unset($this->params['NavPageNomer']);
    }


    protected function collectItem(
        XMLReader $reader,
        callable $fetchCallback

    ) {
        if ($this->isOpenTag($reader, 'item')) {
            $item = [];
            do {
                $reader->read();
                $this->collectField($reader, 'field', $item);
                $this->collectField($reader, 'property', $item);

            } while (!$this->isCloseTag($reader, 'item'));

            if (!empty($item)) {
                call_user_func($fetchCallback, $item);
            }
        }
    }

    protected function collectField(
        XMLReader $reader,
        $tag,
        &$item
    ) {
        if ($this->isOpenTag($reader, $tag)) {
            $name = $reader->getAttribute('name');

            if ($reader->isEmptyElement) {
                $item[$tag][$name] = '';
                return;
            }

            do {
                $reader->read();

                if ($this->isOpenTag($reader, 'value')) {
                    $reader->read();
                    $item[$tag][$name][] = trim($reader->value);
                } elseif ($reader->nodeType == XMLReader::TEXT) {
                    $item[$tag][$name] = trim($reader->value);
                }

            } while (!$this->isCloseTag($reader, $tag));
        }
    }

    protected function isOpenTag(
        XMLReader $reader,
        $tag
    ) {
        return ($reader->nodeType == XMLReader::ELEMENT && $reader->name == $tag);
    }

    protected function isCloseTag(
        XMLReader $reader,
        $tag
    ) {
        return ($reader->nodeType == XMLReader::END_ELEMENT && $reader->name == $tag);
    }
}
