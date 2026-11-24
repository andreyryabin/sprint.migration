<?php

namespace Sprint\Migration\Fields;

use ReflectionClass;
use Sprint\Migration\Exceptions\ConsoleException;
use Sprint\Migration\Interfaces\ConsoleInterface;

abstract class BuilderField
{
    private string $name;
    private array $props = [];
    private string $note = '';
    private bool $inPlan = true;
    private bool $isRequired = false;
    private array $inPlanValues = [];

    public function __construct(private string $title = '') {}

    abstract public function getDefaultValue(): mixed;

    /**
     * @throws ConsoleException
     */
    public function readValueFromConsole(ConsoleInterface $io, string $code): string
    {
        return $io->read("[pink](%s) - %s:[/] ", $code, $this->getTitle());
    }

    public function makeValueFromConsole(string $rawValue): mixed
    {
        return trim($rawValue);
    }

    public function checkRequiredValue(mixed $value): bool
    {
        return !empty($value);
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

    public function getPlanForValue(mixed $value): string
    {
        if (!empty($this->inPlanValues)) {
            $values = is_array($value) ? $value : [$value];
            $allowed = !empty(array_intersect($values, $this->inPlanValues));
        } else {
            $allowed = $this->inPlan;
        }

        return $allowed ? $this->makePlanForValue($value) : '';
    }

    protected function makePlanForValue(string $value): string
    {
        return ($value) ? $this->getTitle() . ': ' . $value : '';
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