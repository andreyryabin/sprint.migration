<?php

namespace Sprint\Migration;

use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;
use CDBResult;
use CMain;
use ReflectionClass;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Traits\OutTrait;

class Helper
{
    use OutTrait;

    /**
     * @throws HelperException
     */
    public function __construct()
    {
        if (!$this->isEnabled()) {
            throw new HelperException(
                Locale::getMessage(
                    'ERR_HELPER_DISABLED',
                    [
                        '#NAME#' => $this->getHelperName(),
                    ]
                )
            );
        }
    }

    public function isEnabled()
    {
        return true;
    }

    protected function checkModules(array $names = []): bool
    {
        $names = is_array($names) ? $names : [$names];
        foreach ($names as $name) {
            try {
                if (!Loader::includeModule($name)) {
                    return false;
                }
            } catch (LoaderException $e) {
                return false;
            }
        }
        return true;
    }

    /**
     * @throws HelperException
     */
    protected function throwApplicationExceptionIfExists(): void
    {
        /* @global $APPLICATION CMain */
        global $APPLICATION;
        if ($APPLICATION->GetException()) {
            throw new HelperException(
                $APPLICATION->GetException()->GetString()
            );
        }
    }

    protected function getHelperName(): string
    {
        return (new ReflectionClass($this))->getShortName();
    }

    protected function hasDiff($exists, $fields): bool
    {
        return ($exists != $fields);
    }

    /**
     * @param $exists
     * @param $fields
     *
     * @return bool
     */
    protected function hasDiffStrict($exists, $fields): bool
    {
        return ($exists !== $fields);
    }

    /**
     * @throws HelperException
     */
    protected function checkRequiredKeys(array $fields, array $reqKeys = []): void
    {
        foreach ($reqKeys as $name) {
            if (empty($fields[$name])) {
                throw new HelperException(
                    Locale::getMessage(
                        'ERR_EMPTY_REQ_FIELD',
                        [
                            '#NAME#' => $name,
                        ]
                    )
                );
            }
        }
    }

    protected function fetchAll(CDBResult $dbres, string $indexKey = '', string $valueKey = ''): array
    {
        $res = [];

        while ($item = $dbres->Fetch()) {
            if ($valueKey) {
                $value = $item[$valueKey];
            } else {
                $value = $item;
            }

            if ($indexKey) {
                $indexVal = $item[$indexKey];
                $res[$indexVal] = $value;
            } else {
                $res[] = $value;
            }
        }

        return $res;
    }

    protected function filterByKey(array $items, string $key, $value): array
    {
        return array_values(
            array_filter(
                $items,
                function ($item) use ($key, $value) {
                    return ($item[$key] == $value);
                }
            )
        );
    }

    protected function merge(array $item, array $default): array
    {
        return array_merge($default, $item);
    }

    protected function mergeCollection(array $collection, array $default): array
    {
        return array_map(function ($item) use ($default) {
            return $this->merge($item, $default);
        }, $collection);
    }

    protected function export(array $item, array $unsetDefault, array $unsetKeys): array
    {
        foreach ($unsetKeys as $key) {
            if (array_key_exists($key, $item)) {
                unset($item[$key]);
            }
        }

        //value может быть null
        foreach ($item as $key => $value) {
            if (array_key_exists($key, $unsetDefault) && $unsetDefault[$key] === $value) {
                unset($item[$key]);
            }
        }

        return $item;
    }

    protected function exportCollection(array $collection, array $unsetDefault, array $unsetKeys): array
    {
        return array_map(function ($item) use ($unsetDefault, $unsetKeys) {
            return $this->export($item, $unsetDefault, $unsetKeys);
        }, $collection);
    }
}
