<?php

namespace Sprint\Migration\Exchange;


use CFile;

class WriterTag
{
    private string $name;
    private array $attributes = [];
    private string $text = '';
    private array $childs = [];
    private array $files = [];
    private bool $cdata = false;

    public function __construct(string $name, array $attributes = [])
    {
        $this->name = $name;

        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getText(): string
    {
        return $this->text;
    }

    public function addChild(WriterTag $child): void
    {
        $this->childs[] = $child;

        foreach ($child->forgetFiles() as $file) {
            $this->files[] = $file;
        }
    }

    public function getChilds(): array
    {
        return $this->childs;
    }

    public function setAttribute(string $name, $value): void
    {
        if ($name && $value) {
            $this->attributes[$name] = $value;
        }

    }

    public function countChilds(): int
    {
        return count($this->childs);
    }

    public function setText(string $text): void
    {
        $this->text = htmlspecialchars_decode($text);
        $this->cdata = $this->text != $text;
    }

    public function setJson(array $text): void
    {
        $this->text = json_encode($text, JSON_UNESCAPED_UNICODE);
        $this->cdata = true;
        $this->setAttribute('type', 'json');
    }

    public function isCdata(): bool
    {
        return $this->cdata;
    }


    public function addFile(mixed $val, bool $multiple): void
    {
        if ($multiple) {
            foreach ($val as $val1) {
                $this->addFileTag($val1);
            }
        } elseif ($val) {
            $this->addFileTag($val);
        }
    }

    public function addValue(mixed $val, bool $multiple): void
    {
        if ($multiple) {
            foreach ($val as $val1) {
                $this->addValueTag($val1);
            }
        } else {
            $this->addValueTag($val);
        }
    }


    public function addValueTag($val, $attributes = []): void
    {
        if (empty($val)) {
            return;
        }

        $tag = new WriterTag('value', $attributes);
        if (is_array($val)) {
            $tag->setJson($val);
        } else {
            $tag->setText($val);
        }

        $this->addChild($tag);
    }

    public function addFileTag(int $fileId): void
    {
        $file = CFile::GetFileArray($fileId);
        if (empty($file)) {
            return;
        }

        $this->addValueTag(
            $file['SUBDIR'] . '/' . $file['FILE_NAME'],
            [
                'name' => $file['ORIGINAL_NAME'],
                'description' => $file['DESCRIPTION'],
                'type' => 'file',
            ]
        );

        $this->files[] = $file;
    }

    public function getFiles(): array
    {
        return $this->files;
    }

    public function forgetFiles(): array
    {
        $tmp = $this->files;

        $this->files = [];

        return $tmp;
    }
}
