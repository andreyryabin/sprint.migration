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
    public function isEnabled()
    {
        return (
            class_exists('\Bitrix\Main\ModuleManager') && class_exists('\Bitrix\Main\Entity\DataManager') && class_exists('\Bitrix\Main\Config\Option')
        );
    }

    /**
     * @return array|mixed
     */
    public function getModules()
    {
        return ModuleManager::getInstalledModules();
    }

    /**
     * @param array $filter
     *
     * @throws HelperException
     * @return array
     */
    public function getOptions($filter = [])
    {
        $this->checkRequiredKeys(__METHOD__, $filter, ['MODULE_ID']);

        try {
            $values = Option::getForModule($filter['MODULE_ID']);
        } catch (Exception $e) {
            $values = [];
        }

        $result = [];
        foreach ($values as $optionName => $value) {
            $result[] = $this->prepareOption([
                'MODULE_ID' => $filter['MODULE_ID'],
                'NAME'      => $optionName,
                'VALUE'     => $value,
            ]);
        }

        return $result;
    }

    /**
     * @param array $filter
     *
     * @throws HelperException
     * @return array|false
     */
    public function getOption($filter = [])
    {
        $this->checkRequiredKeys(__METHOD__, $filter, ['MODULE_ID', 'NAME']);

        try {
            $value = Option::get($filter['MODULE_ID'], $filter['NAME']);
            return $this->prepareOption([
                'MODULE_ID' => $filter['MODULE_ID'],
                'NAME'      => $filter['NAME'],
                'VALUE'     => $value,
            ]);
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * @param $fields
     *
     * @throws HelperException
     * @return bool
     */
    public function saveOption($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['MODULE_ID', 'NAME']);

        $exists = $this->getOption([
            'MODULE_ID' => $fields['MODULE_ID'],
            'NAME'      => $fields['NAME'],
        ]);

        if (empty($exists)) {
            $ok = $this->getMode('test') || $this->setOption($fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'OPTION_CREATED',
                    [
                        '#NAME#' => $fields['MODULE_ID'] . ':' . $fields['NAME'],
                    ]
                )
            );
            return $ok;
        }

        if ($this->hasDiff($exists, $fields)) {
            $ok = $this->getMode('test') || $this->setOption($fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'OPTION_UPDATED',
                    [
                        '#NAME#' => $fields['MODULE_ID'] . ':' . $fields['NAME'],
                    ]
                )
            );
            $this->outDiffIf($ok, $exists, $fields);
            return $ok;
        }

        $ok = true;
        if ($this->getMode('out_equal')) {
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'OPTION_EQUAL',
                    [
                        '#NAME#' => $fields['MODULE_ID'] . ':' . $fields['NAME'],
                    ]
                )
            );
        }
        return $ok;
    }

    /**
     * @param array $filter
     *
     * @throws HelperException
     * @return bool
     */
    public function deleteOptions($filter = [])
    {
        $this->checkRequiredKeys(__METHOD__, $filter, ['MODULE_ID']);

        $params = [];

        if (isset($filter['NAME'])) {
            $params['name'] = $filter['NAME'];
        }

        try {
            Option::delete($filter['MODULE_ID'], $params);
            return true;
        } catch (Exception $e) {
        }

        return false;
    }

    /**
     * @param $fields
     *
     * @return bool
     */
    protected function setOption($fields)
    {
        $fields = $this->revertOption($fields);
        try {
            Option::set($fields['MODULE_ID'], $fields['NAME'], $fields['VALUE']);
            return true;
        } catch (Exception $e) {
        }
        return false;
    }

    /**
     * @param $item
     *
     * @return array
     */
    protected function prepareOption($item)
    {
        if (!empty($item['VALUE']) && !is_numeric($item['VALUE'])) {
            if ($this->isSerialize($item['VALUE'])) {
                $item['VALUE'] = unserialize($item['VALUE']);
            } elseif ($this->isJson($item['VALUE'])) {
                $item['VALUE'] = json_decode($item['VALUE'], true);
                $item['TYPE'] = 'json';
            }
        }
        return $item;
    }

    protected function revertOption($item)
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

    protected function isSerialize($string)
    {
        return (unserialize($string) !== false || $string == 'b:0;');
    }

    protected function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}
