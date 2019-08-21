<?php

namespace Sprint\Migration\Exchange;

use Sprint\Migration\AbstractExchange;
use Sprint\Migration\Exceptions\RestartException;
use XMLReader;

class IblockElementsImport extends AbstractExchange
{
    protected $file;

    protected $limit = 10;

    /**
     * @var callable
     */
    protected $callback;

    public function isEnabled()
    {
        return (
            $this->getHelperManager()->Iblock()->isEnabled() &&
            class_exists('XMLReader') &&
            class_exists('XMLWriter')
        );
    }

    /**
     * @param callable $callback
     * @throws RestartException
     */
    public function process(callable $callback)
    {

        $this->callback = $callback;

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

        $index = 0;

        $reader = new XMLReader();
        $reader->open($this->file);

        while ($reader->read()) {
            if ($this->isOpenTag($reader, 'item')) {

                $collect = (
                    $index >= $this->params['pos'] &&
                    $index < $this->params['pos'] + $this->getLimit()
                );

                $finish = ($index >= $this->params['all'] - 1);
                $restart = ($index >= $this->params['pos'] + $this->getLimit());

                if ($collect) {
                    $this->collectItem($reader);
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
        unset($this->params['pos']);
        unset($this->params['all']);
    }


    protected function collectItem(XMLReader $reader)
    {
        if ($this->isOpenTag($reader, 'item')) {
            $item = [];
            do {
                $reader->read();
                $this->collectField($reader, 'field', $item);
                $this->collectField($reader, 'property', $item);

            } while (!$this->isCloseTag($reader, 'item'));

            if (!empty($item) && is_callable($this->callback)) {
                call_user_func($this->callback, $item);
            }
        }
    }

    protected function collectField(XMLReader $reader, $tag, &$item)
    {
        if ($this->isOpenTag($reader, $tag)) {
            $name = $reader->getAttribute('name');
            do {
                $reader->read();
                if ($this->isOpenTag($reader, 'value')) {
                    $reader->read();
                    $item[$tag][$name][] = $this->prepareValue($reader->value);
                } elseif ($reader->nodeType == XMLReader::TEXT) {
                    $item[$tag][$name] = $this->prepareValue($reader->value);
                }

            } while (!$this->isCloseTag($reader, $tag));
        }
    }

    protected function prepareValue($value)
    {
        $value = trim($value);

        $search = [
            "'&(quot|#34);'i",
            "'&(lt|#60);'i",
            "'&(gt|#62);'i",
            "'&(amp|#38);'i",
        ];

        $replace = [
            "\"",
            "<",
            ">",
            "&",
        ];

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
        return ($reader->nodeType == XMLReader::ELEMENT && $reader->name == $tag && !$reader->isEmptyElement);
    }

    protected function isCloseTag(XMLReader $reader, $tag)
    {
        return ($reader->nodeType == XMLReader::END_ELEMENT && $reader->name == $tag);
    }

    /**
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    public function from($file)
    {
        $this->file = $file;
        return $this;
    }

}
