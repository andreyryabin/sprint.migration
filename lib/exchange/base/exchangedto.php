<?php

namespace Sprint\Migration\Exchange\Base;


use CFile;

class ExchangeDto
{
    private string $name;
    private array $attributes = [];
    private string $text = '';
    private array $childs = [];
    private array $files = [];

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

    public function addChild(ExchangeDto $child): void
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
    }

    public function setTextJson(array $text): void
    {
        $this->setText(json_encode($text, JSON_UNESCAPED_UNICODE));
        $this->setAttribute('type', 'json');
    }

    public function addFile(mixed $val): void
    {
        if (is_array($val)) {
            foreach ($val as $val1) {
                $this->addFileTag($val1);
            }
        } elseif($val) {
            $this->addFileTag($val);
        }
    }

    public function addValue(mixed $val, $attributes = []): void
    {
        if (is_array($val)) {
            foreach ($val as $val1) {
                $this->addValueTag($val1, $attributes);
            }
        } else {
            $this->addValueTag($val, $attributes);
        }
    }


    private function addValueTag($val, $attributes = []): void
    {
        if (empty($val)) {
            return;
        }

        $dto = new ExchangeDto('value', $attributes);
        if (is_array($val)) {
            $dto->setTextJson($val);
        } else {
            $dto->setText($val);
        }

        $this->addChild($dto);
    }

    private function addFileTag(int $fileId): void
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
