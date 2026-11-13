<?php

namespace Sprint\Migration\Fields;

use ReflectionClass;

abstract class AbstractField
{
    private string $name;
    private array $props = [];
    private string $title = '';
    private string $note = '';

    public function __construct(private readonly string $code)
    {
    }

    abstract public function getDefaultValue(): mixed;

    public function getCode(): string
    {
        return $this->code;
    }

    public function getComponentName(): string
    {
        return $this->name ??= (new ReflectionClass($this))->getShortName();
    }

    public function getProps(): array
    {
        return $this->props;
    }


    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function setNote(string $note): static
    {
        $this->note = $note;
        return $this;
    }


    protected function setProp(string $name, mixed $value): static
    {
        $this->props[$name] = $value;
        return $this;
    }

    protected function addToProp(string $name, mixed $value): static
    {
        if (isset($this->props[$name])) {
            $this->props[$name][] = $value;
        } else {
            $this->props[$name] = [$value];
        }
        return $this;
    }


}