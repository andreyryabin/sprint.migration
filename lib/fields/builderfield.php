<?php

namespace Sprint\Migration\Fields;

use ReflectionClass;

abstract class BuilderField
{
    private string $name;
    private array $props = [];
    private string $note = '';
    private bool $inPlan = true;
    private bool $isRequired = false;
    private array $inPlanValues = [];

    public function __construct(private string $title = '') {}

    abstract public function decodeValue(mixed $rawValue): mixed;
    abstract public function encodeValue(mixed $validValue): mixed;

    abstract public function planValue(mixed $validValue): string;

    public function getName(): string
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

    /**
     * Установить значения, для которых поле попадает в план.
     *   ->setInPlanForValues('all') - только для 'all'
     *   ->setInPlanForValues('all', 'some') - для 'all' или 'some'
     */
    public function setInPlanForValues(mixed ...$values): static
    {
        $this->inPlanValues = $values;
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->isRequired;
    }

    public function setRequired(bool $isRequired): void
    {
        $this->isRequired = $isRequired;
    }

    protected function isPlanAllowed(mixed $validValue): bool
    {
        if (!empty($this->inPlanValues)) {
            $values = is_array($validValue) ? $validValue : [$validValue];
            $allowed = !empty(array_intersect($values, $this->inPlanValues));
        } else {
            $allowed = $this->inPlan && !empty($validValue);
        }
        return $allowed;
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