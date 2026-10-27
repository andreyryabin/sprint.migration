<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class OptionHelper extends Helper
{
    public function isEnabled(): bool
    {
        return (
            class_exists('\Bitrix\Main\ModuleManager')
            && class_exists('\Bitrix\Main\Entity\DataManager')
            && class_exists('\Bitrix\Main\Config\Option')
        );
    }

    public function getModules(array $filter = []): array
    {
        $modules = ModuleManager::getInstalledModules();

        if (isset($filter['!ID'])) {
            $skipModules = is_array($filter['!ID']) ? $filter['!ID'] : [$filter['!ID']];

            $modules = array_filter($modules, function ($module) use ($skipModules) {
                return !in_array($module['ID'], $skipModules);
            });
        }

        return $modules;
    }

    /**
     * @throws HelperException
     */
    public function getOptions(array $filter = []): array
    {
        $this->checkRequiredKeys($filter, ['MODULE_ID']);

        $sites = (array)($filter['SITE_ID'] ?? [false]);

        $result = [];

        foreach ($sites as $siteId) {
            try {
                $options = Option::getForModule($filter['MODULE_ID'], $siteId);
            } catch (Exception $e) {
                throw new HelperException($e->getMessage());
            }

            foreach ($options as $optionName => $optionValue) {
                $result[] = $this->transformOption([
                    'MODULE_ID' => $filter['MODULE_ID'],
                    'NAME'      => $optionName,
                    'VALUE'     => $optionValue,
                    'SITE_ID'   => $siteId
                ]);
            }
        }

        return $result;
    }

    /**
     * @throws HelperException
     */
    public function getOption(array $filter = []): array
    {
        $this->checkRequiredKeys($filter, ['MODULE_ID', 'NAME']);

        try {
            $value = Option::get(
                $filter['MODULE_ID'],
                $filter['NAME'],
                $filter['DEFAULT_VALUE'] ?? '',
                $filter['SITE_ID'] ?? false
            );

            return $this->transformOption([
                'MODULE_ID' => $filter['MODULE_ID'],
                'NAME'      => $filter['NAME'],
                'VALUE'     => $value,
                'SITE_ID'   => $filter['SITE_ID'],
            ]);

        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function saveOption(array $fields): bool
    {
        $this->checkRequiredKeys($fields, ['MODULE_ID', 'NAME']);

        $exists = $this->getOption([
            'MODULE_ID'     => $fields['MODULE_ID'],
            'NAME'          => $fields['NAME'],
            'VALUE'         => $fields['VALUE'] ?? '',
            'DEFAULT_VALUE' => $fields['DEFAULT_VALUE'] ?? '',
            'SITE_ID'       => $fields['SITE_ID'] ?? false,
        ]);

        if (empty($exists)) {
            return $this->setOption($fields);
        }

        if ($this->checkDiff($exists, $fields)) {
            return $this->setOption($fields);
        }

        return true;
    }

    /**
     * @throws HelperException
     */
    public function deleteOptions(array $filter = []): void
    {
        $this->checkRequiredKeys($filter, ['MODULE_ID']);

        $params = [];

        if (isset($filter['NAME'])) {
            $params['name'] = $filter['NAME'];
        }

        if (isset($filter['SITE_ID'])) {
            $params['site_id'] = $filter['SITE_ID'];
        }

        try {
            Option::delete($filter['MODULE_ID'], $params);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage());
        }
    }

    /**
     * @throws HelperException
     */
    protected function setOption(array $fields): bool
    {
        $fields = $this->revertOption($fields);
        try {
            Option::set(
                $fields['MODULE_ID'],
                $fields['NAME'],
                $fields['VALUE'],
                $fields['SITE_ID']
            );

            $optionName = implode(':', array_filter([
                $fields['MODULE_ID'],
                $fields['SITE_ID'],
                $fields['NAME']
            ]));


            $this->outNotice(Locale::getMessage(
                'OPTION_UPDATED',
                ['#NAME#' => $optionName])
            );

            return true;
        } catch (Exception $e) {
            throw new HelperException($e->getMessage());
        }
    }

    protected function transformOption(array $item): array
    {
        if (!empty($item['VALUE']) && !is_numeric($item['VALUE'])) {
            if ($this->isSerialize($item['VALUE'])) {
                $item['VALUE'] = unserialize($item['VALUE'], ['allowed_classes' => false]);
            } elseif ($this->isJson($item['VALUE'])) {
                $item['VALUE'] = json_decode($item['VALUE'], true);
                $item['TYPE'] = 'json';
            }
        }
        return $item;
    }

    protected function revertOption(array $item): array
    {
        $type = '';
        if (isset($item['TYPE'])) {
            $type = $item['TYPE'];
            unset($item['TYPE']);
        }

        if (is_array($item['VALUE'])) {
            if ($type == 'json') {
                $item['VALUE'] = json_encode($item['VALUE']);
            } else {
                $item['VALUE'] = serialize($item['VALUE']);
            }
        }

        return $item;
    }

    protected function isSerialize($string): bool
    {
        return (unserialize($string, ['allowed_classes' => false]) !== false || $string == 'b:0;');
    }

    protected function isJson($string): bool
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
