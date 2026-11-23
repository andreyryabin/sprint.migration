<?php

namespace Sprint\Migration\Fields;

use ReflectionClass;
use Sprint\Migration\Exceptions\ConsoleException;
use Sprint\Migration\Interfaces\ConsoleInterface;

abstract class AbstractField
{
    private string $name;
    private array $props = [];
    private string $title = '';
    private string $note = '';
    private bool $inPlan = true;
    private bool $isRequired = false;

    public function __construct(private readonly string $code) {}

    abstract public function getDefaultValue(): mixed;

    /**
     * @throws ConsoleException
     */
    public function readValueFromConsole(ConsoleInterface $io): string
    {
        return $io->read("[pink](%s) - %s:[/] ", $this->getCode(), $this->getTitle());
    }

    public function makeValueFromConsole(string $rawValue): mixed
    {
        return trim($rawValue);
    }

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
    public function setInPlan(bool $inPlan): static
    {
        $this->inPlan = $inPlan;
        return $this;
    }

    public function isInPlan(): bool
    {
        return $this->inPlan;
    }

    public function makePlanText(mixed $value): string
    {
        return (string)$value;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setRequired(bool $isRequired): void
    {
        $this->isRequired = $isRequired;
    }

    protected function getProp(string $name, mixed $defaultValue): mixed
    {
        return $this->props[$name] ?? $defaultValue;
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