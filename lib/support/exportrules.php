<?php

namespace Sprint\Migration\Support;

class ExportRules
{
    protected array $defaultItem = [];
    protected array $unsetKeys   = [];

    public function getDefaultItem(): array
    {
        return $this->defaultItem;
    }

    public function getUnsetKeys(): array
    {
        return $this->unsetKeys;
    }

    public function unsetKeys(array $unsetKeys): ExportRules
    {
        $this->unsetKeys = $unsetKeys;
        return $this;
    }

    public function setDefault(array $item): ExportRules
    {
        $this->defaultItem = $item;
        return $this;
    }

    public function merge(array $item): array
    {
        return array_merge($this->getDefaultItem(), $item);
    }

    public function export(array $item): array
    {
        $defaultItem = $this->getDefaultItem();

        foreach ($this->getUnsetKeys() as $key) {
            if (array_key_exists($key, $item)) {
                unset($item[$key]);
            }
        }

        //value может быть null
        foreach ($item as $key => $value) {
            if (array_key_exists($key, $defaultItem) && $defaultItem[$key] === $value) {
                unset($item[$key]);
            }
        }

        return $item;
    }

    public function exportCollection(array $collection): array
    {
        return array_map(function ($item) {
            return $this->export($item);
        }, $collection);
    }

    public function mergeCollection(array $collection): array
    {
        return array_map(function ($item) {
            return $this->merge($item);
        }, $collection);
    }
}
