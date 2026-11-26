<?php

namespace Sprint\Migration\Fields;

use Sprint\Migration\Helpers\TextHelper;

class TextareaField extends BuilderField
{

    private bool $explode = false;
    private string $separator = ' ';
    private bool $numeric = false;
    private bool $unique = true;

    public function setRows(int $rowsCnt): static
    {
        return $this->setProp('rows', $rowsCnt);
    }

    public function setCols(int $colsCnt): static
    {
        return $this->setProp('cols', $colsCnt);
    }

    public function setPlaceholder(string $placeholder): static
    {
        return $this->setProp('placeholder', $placeholder);
    }
    public function explodeToNumeric(): static
    {
        $this->explode = true;
        $this->numeric = true;
        return $this;
    }
    public function explodeToStrings(): static
    {
        $this->explode = true;
        $this->numeric = false;
        return $this;
    }

    public function decodeValue(mixed $rawValue): mixed
    {
        if ($this->explode) {
            return (new TextHelper())->explodeValues(
                $rawValue,
                $this->separator,
                $this->numeric,
                $this->unique,
            );
        }

        return (string) $rawValue;
    }

    public function planValue(mixed $validValue): string
    {
        if (!$this->isPlanAllowed($validValue)) {
            return '';
        }

        if ($this->explode) {
            return $this->getTitle() . ': ' . implode(', ', $validValue);
        }

        return $this->getTitle() . ': ' . $validValue;
    }

    public function encodeValue(mixed $validValue): mixed
    {
        if ($this->explode) {
            return implode($this->separator, $validValue);
        }

        return (string) $validValue;
    }
}