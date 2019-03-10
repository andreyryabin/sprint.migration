<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main\ModuleManager;
use Sprint\Migration\Helper;
use Sprint\Migration\Tables\OptionTable;

class OptionHelper extends Helper
{

    public function isEnabled() {
        return (
            class_exists('\Bitrix\Main\ModuleManager') &&
            class_exists('\Bitrix\Main\Entity\DataManager') &&
            class_exists('\Bitrix\Main\Config\Option')
        );
    }

    /**
     * @return array|mixed
     */
    public function getModules() {
        return ModuleManager::getInstalledModules();
    }

    /**
     * @param array $filter
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    public function getOptions($filter = array()) {
        $dbres = OptionTable::getList(array(
            'filter' => $filter
        ));

        $result = array();
        while ($item = $dbres->fetch()) {
            $result[] = $this->prepareOption($item);
        }

        return $result;
    }

    /**
     * @param array $filter , обязательные параметры - id модуля, функция агента
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     */
    public function getOption($filter = array()) {
        $this->checkRequiredKeys(__METHOD__, $filter, array('MODULE_ID', 'NAME'));

        $item = OptionTable::getList(array(
            'filter' => $filter
        ))->fetch();

        return $this->prepareOption($item);
    }

    /**
     * @param $fields , обязательные параметры - id модуля, функция агента
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     */
    public function saveOption($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('MODULE_ID', 'NAME'));

        $exists = $this->getOption(array(
            'MODULE_ID' => $fields['MODULE_ID'],
            'NAME' => $fields['NAME'],
            'SITE_ID' => $fields['SITE_ID'],
        ));

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->setOption($fields);
            $this->outNoticeIf($ok, 'Настройка %s:%s: добавлена', $fields['MODULE_ID'], $fields['NAME']);
            return $ok;
        }

        if ($this->hasDiff($exists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->setOption($fields);
            $this->outNoticeIf($ok, 'Настройка %s:%s: обновлена', $fields['MODULE_ID'], $fields['NAME']);
            $this->outDiffIf($ok, $exists, $fields);
            return $ok;
        }


        $ok = true;
        if ($this->getMode('out_equal')) {
            $this->outIf($ok, 'Настройка %s:%s: совпадает', $fields['MODULE_ID'], $fields['NAME']);
        }
        return $ok;

    }

    /**
     * @param array $filter , обязательные параметры - id модуля
     * @return bool
     * @throws \Bitrix\Main\ArgumentNullException
     */
    public function deleteOptions($filter = array()) {
        $this->checkRequiredKeys(__METHOD__, $filter, array('MODULE_ID'));

        $params = array();

        if (isset($filter['NAME'])) {
            $params['name'] = $filter['NAME'];
        }

        if (isset($filter['SITE_ID'])) {
            $params['site_id'] = $filter['SITE_ID'];
        }

        \Bitrix\Main\Config\Option::delete($filter['MODULE_ID'], $params);
        return true;
    }

    /**
     * @param array $filter
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    public function exportOptions($filter = array()) {
        $agents = $this->getOptions($filter);

        $exportAgents = array();
        foreach ($agents as $agent) {
            $exportAgents[] = $this->prepareExportOption($agent);
        }

        return $exportAgents;
    }

    /**
     * @param array $filter
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     */
    public function exportOption($filter = array()) {
        $item = $this->getOption($filter);
        if (empty($item)) {
            return false;
        }

        return $this->prepareExportOption($item);
    }

    /**
     * @param $fields
     * @return bool
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    protected function setOption($fields) {
        $fields = $this->revertOption($fields);
        \Bitrix\Main\Config\Option::set($fields['MODULE_ID'], $fields['NAME'], $fields['VALUE'], $fields['SITE_ID']);
        return true;
    }

    protected function prepareExportOption($item) {
        if (empty($item)) {
            return $item;
        }
        return $item;
    }

    protected function prepareOption($item) {
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

    protected function revertOption($item) {
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

    protected function isSerialize($string) {
        return (unserialize($string) !== false || $string == 'b:0;');
    }

    protected function isJson($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}