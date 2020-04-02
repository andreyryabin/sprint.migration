<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ArgumentNullException;
use Bitrix\Main\ArgumentOutOfRangeException;
use Bitrix\Main\Config\Option;
use Bitrix\Main\ModuleManager;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;
use Sprint\Migration\Tables\OptionTable;

class OptionHelper extends Helper
{

    public function isEnabled()
    {
        return (
            class_exists('\Bitrix\Main\ModuleManager') &&
            class_exists('\Bitrix\Main\Entity\DataManager') &&
            class_exists('\Bitrix\Main\Config\Option')
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
     * @throws ArgumentException
     * @throws SystemException
     * @return array
     */
    public function getOptions($filter = [])
    {
        $dbres = OptionTable::getList([
            'filter' => $filter,
        ]);

        $result = [];
        while ($item = $dbres->fetch()) {
            $result[] = $this->prepareOption($item);
        }

        return $result;
    }

    /**
     * @param array $filter , обязательные параметры - id модуля, функция агента
     * @throws ArgumentException
     * @throws SystemException
     * @throws ObjectPropertyException
     * @throws HelperException
     * @return mixed
     */
    public function getOption($filter = [])
    {
        $this->checkRequiredKeys(__METHOD__, $filter, ['MODULE_ID', 'NAME']);

        $item = OptionTable::getList([
            'filter' => $filter,
        ])->fetch();

        return $this->prepareOption($item);
    }

    /**
     * @param $fields , обязательные параметры - id модуля, функция агента
     * @throws ArgumentException
     * @throws ArgumentOutOfRangeException
     * @throws SystemException
     * @throws ObjectPropertyException
     * @throws HelperException
     * @return bool
     */
    public function saveOption($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['MODULE_ID', 'NAME']);

        $exists = $this->getOption([
            'MODULE_ID' => $fields['MODULE_ID'],
            'NAME' => $fields['NAME'],
            'SITE_ID' => $fields['SITE_ID'],
        ]);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->setOption($fields);
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
            $ok = $this->getMode('test') ? true : $this->setOption($fields);
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
     * @param array $filter , обязательные параметры - id модуля
     * @throws ArgumentNullException
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

        if (isset($filter['SITE_ID'])) {
            $params['site_id'] = $filter['SITE_ID'];
        }

        Option::delete($filter['MODULE_ID'], $params);
        return true;
    }

    /**
     * @param array $filter
     * @throws ArgumentException
     * @throws SystemException
     * @return array
     */
    public function exportOptions($filter = [])
    {
        $agents = $this->getOptions($filter);

        $exportAgents = [];
        foreach ($agents as $agent) {
            $exportAgents[] = $this->prepareExportOption($agent);
        }

        return $exportAgents;
    }

    /**
     * @param array $filter
     * @throws ArgumentException
     * @throws SystemException
     * @throws ObjectPropertyException
     * @throws HelperException
     * @return bool
     */
    public function exportOption($filter = [])
    {
        $item = $this->getOption($filter);
        if (empty($item)) {
            return false;
        }

        return $this->prepareExportOption($item);
    }

    /**
     * @param $fields
     * @throws ArgumentOutOfRangeException
     * @return bool
     */
    protected function setOption($fields)
    {
        $fields = $this->revertOption($fields);
        Option::set($fields['MODULE_ID'], $fields['NAME'], $fields['VALUE'], $fields['SITE_ID']);
        return true;
    }

    protected function prepareExportOption($item)
    {
        if (empty($item)) {
            return $item;
        }
        return $item;
    }

    protected function prepareOption($item)
    {
        if (!empty($item['VALUE']) && !is_numeric($item['VALUE'])) {
            if ($this->isSerialize($item['VALUE'])) {
                $item['VALUE'] = unserialize($item['VALUE']);
            } elseif ($this->isJson($item['VALUE'])) {
                $item['VALUE'] = json_decode($item['VALUE'], true);
                $item['TYPE'] = 'json';
            } elseif (is_int($item['VALUE'])) {
                $item['VALUE'] = intval($item['VALUE']);
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
        } elseif (is_int($item['VALUE'])) {
            $item['VALUE'] = intval($item['VALUE']);
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
