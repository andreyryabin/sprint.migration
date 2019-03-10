<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

use Bitrix\Highloadblock as HL;


class HlblockHelper extends Helper
{

    public function __construct() {
        $this->checkModules(array('highloadblock'));
    }

    /**
     * Получает список highload-блоков
     * @param array $filter
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    public function getHlblocks($filter = array()) {
        $dbres = HL\HighloadBlockTable::getList(
            array(
                'select' => array('*'),
                'filter' => $filter,
            )
        );

        $result = [];
        while ($hlblock = $dbres->fetch()) {
            $hlblock['LANG'] = $this->getHblockLangs($hlblock['ID']);
            $result[] = $hlblock;
        }

        return $result;
    }

    /**
     * Получает список highload-блоков
     * Данные подготовлены для экспорта в миграцию или схему
     * @param array $filter
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    public function exportHlblocks($filter = array()) {
        $items = $this->getHlblocks($filter);

        $export = array();
        foreach ($items as $item) {
            $export[] = $this->prepareExportHlblock($item);
        }

        return $export;
    }

    /**
     * Получает список полей highload-блока
     * @param $hlblockName int|string|array - id, имя или фильтр
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    public function getFields($hlblockName) {
        $hlblockId = is_numeric($hlblockName) ? $hlblockName : $this->getHlblockId($hlblockName);

        $entityHelper = new UserTypeEntityHelper();
        $entityHelper->setMode($this);
        return $entityHelper->getUserTypeEntities('HLBLOCK_' . $hlblockId);
    }

    /**
     * Сохраняет поле highload-блока
     * Создаст если не было, обновит если существует и отличается
     * @param $hlblockName int|string|array - id, имя или фильтр
     * @param array $field
     * @return bool|int|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function saveField($hlblockName, $field = array()) {
        $hlblockId = is_numeric($hlblockName) ? $hlblockName : $this->getHlblockId($hlblockName);
        $field['ENTITY_ID'] = 'HLBLOCK_' . $hlblockId;

        $entityHelper = new UserTypeEntityHelper();
        $entityHelper->setMode($this);
        return $entityHelper->saveUserTypeEntity($field);
    }

    /**
     * Сохраняет highload-блок
     * Создаст если не было, обновит если существует и отличается
     * @param $fields , обязательные параметры - название сущности
     * @return bool|int|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function saveHlblock($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('NAME'));

        $exists = $this->getHlblock($fields['NAME']);
        $exportExists = $this->prepareExportHlblock($exists);
        $fields = $this->prepareExportHlblock($fields);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->addHlblock($fields);
            $this->outNoticeIf($ok, 'Highload-блок %s: добавлен', $fields['NAME']);
            return $ok;
        }

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updateHlblock($exists['ID'], $fields);
            $this->outNoticeIf($ok, 'Highload-блок %s: обновлен', $fields['NAME']);
            $this->outDiffIf($ok, $exportExists, $fields);
            return $ok;
        }


        $ok = $this->getMode('test') ? true : $exists['ID'];
        if ($this->getMode('out_equal')) {
            $this->outIf($ok, 'Highload-блок %s: совпадает', $fields['NAME']);
        }
        return $ok;
    }


    /**
     * Удаляет поле highload-блока
     * @param $hlblockName
     * @param $fieldName
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function deleteField($hlblockName, $fieldName) {
        $hlblockId = is_numeric($hlblockName) ? $hlblockName : $this->getHlblockId($hlblockName);

        $entityHelper = new UserTypeEntityHelper();
        $entityHelper->setMode($this);
        return $entityHelper->deleteUserTypeEntity('HLBLOCK_' . $hlblockId, $fieldName);
    }

    /**
     * Получает список полей highload-блока
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $hlblockName
     * @return array
     * @throws \Bitrix\Main\ArgumentException
     */
    public function exportFields($hlblockName) {
        $fields = $this->getFields($hlblockName);
        $export = array();
        foreach ($fields as $field) {
            $export[] = $this->prepareExportHlblockField($field);
        }

        return $export;
    }

    /**
     * Получает highload-блок
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $hlblockName
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     */
    public function exportHlblock($hlblockName) {
        return $this->prepareExportHlblock(
            $this->getHlblock($hlblockName)
        );
    }

    /**
     * Получает highload-блок
     * @param $hlblockName - id, имя или фильтр
     * @return array|false
     * @throws \Bitrix\Main\ArgumentException
     */
    public function getHlblock($hlblockName) {
        if (is_array($hlblockName)) {
            $filter = $hlblockName;
        } elseif (is_numeric($hlblockName)) {
            $filter = array('ID' => $hlblockName);
        } else {
            $filter = array('NAME' => $hlblockName);
        }

        $result = HL\HighloadBlockTable::getList(
            array(
                'select' => array('*'),
                'filter' => $filter,
            )
        );

        $hlblock = $result->fetch();
        if ($hlblock) {
            $hlblock['LANG'] = $this->getHblockLangs($hlblock['ID']);
        }

        return $hlblock;
    }

    /**
     * @param $hlblockName
     * @return array|false
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function getHlblockIfExists($hlblockName) {
        $item = $this->getHlblock($hlblockName);
        if ($item && isset($item['ID'])) {
            return $item;
        }

        $this->throwException(__METHOD__, "hlblock not found");
    }

    /**
     * Получает highload-блок, бросает исключение если его не существует
     * @param $hlblockName - id, имя или фильтр
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function getHlblockIdIfExists($hlblockName) {
        $item = $this->getHlblock($hlblockName);
        if ($item && isset($item['ID'])) {
            return $item['ID'];
        }

        $this->throwException(__METHOD__, "hlblock id not found");
    }

    /**
     * Получает id highload-блока
     * @param $hlblockName - id, имя или фильтр
     * @return int|mixed
     * @throws \Bitrix\Main\ArgumentException
     */
    public function getHlblockId($hlblockName) {
        $item = $this->getHlblock($hlblockName);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    /**
     * Добавляет highload-блок
     * @param $fields , обязательные параметры - название сущности, название таблицы в БД
     * @return int
     * @throws \Bitrix\Main\SystemException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addHlblock($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('NAME', 'TABLE_NAME'));

        $lang = array();
        if (isset($fields['LANG'])) {
            $lang = $fields['LANG'];
            unset($fields['LANG']);
        }

        $fields['NAME'] = ucfirst($fields['NAME']);

        $result = HL\HighloadBlockTable::add($fields);
        if ($result->isSuccess()) {
            $this->replaceHblockLangs($result->getId(), $lang);
            return $result->getId();
        }

        $this->throwException(__METHOD__, implode(', ', $result->getErrors()));
    }

    /**
     * Добавляет highload-блок, если его не существует
     * @param $fields , обязательные параметры - название сущности
     * @return int|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\SystemException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addHlblockIfNotExists($fields) {
        $this->checkRequiredKeys(__METHOD__, $fields, array('NAME'));

        $item = $this->getHlblock($fields['NAME']);
        if ($item) {
            return $item['ID'];
        }

        return $this->addHlblock($fields);
    }

    /**
     * Обновляет highload-блок
     * @param $hlblockId
     * @param $fields
     * @return mixed
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function updateHlblock($hlblockId, $fields) {
        $lang = array();
        if (isset($fields['LANG'])) {
            $lang = $fields['LANG'];
            unset($fields['LANG']);
        }

        $result = HL\HighloadBlockTable::update($hlblockId, $fields);
        if ($result->isSuccess()) {
            $this->replaceHblockLangs($hlblockId, $lang);
            return $hlblockId;
        }

        $this->throwException(__METHOD__, implode(', ', $result->getErrors()));
    }

    /**
     * Обновляет highload-блок, если существует
     * @param $hlblockName
     * @param $fields
     * @return bool|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function updateHlblockIfExists($hlblockName, $fields) {
        $item = $this->getHlblock($hlblockName);
        if (!$item) {
            return false;
        }

        return $this->updateHlblock($item['ID'], $fields);
    }

    /**
     * Удаляет highload-блок
     * @param $hlblockId
     * @return bool
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function deleteHlblock($hlblockId) {
        $result = HL\HighloadBlockTable::delete($hlblockId);
        if ($result->isSuccess()) {
            return true;
        }

        $this->throwException(__METHOD__, implode(', ', $result->getErrors()));
    }

    /**
     * Удаляет highload-блок, если существует
     * @param $hlblockName
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function deleteHlblockIfExists($hlblockName) {
        $item = $this->getHlblock($hlblockName);
        if (!$item) {
            return false;
        }

        return $this->deleteHlblock($item['ID']);
    }

    protected function prepareExportHlblockField($item) {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['ENTITY_ID']);

        return $item;
    }

    protected function prepareExportHlblock($item) {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);

        return $item;
    }

    protected function getHblockLangs($hlblockId) {
        $result = array();

        if (!class_exists('Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $result;
        }

        $dbres = HL\HighloadBlockLangTable::getList(array(
            'filter' => array('ID' => $hlblockId)
        ));


        while ($item = $dbres->fetch()) {
            $result[$item['LID']] = array(
                'NAME' => $item['NAME']
            );
        }

        return $result;
    }

    protected function deleteHblockLangs($hlblockId) {
        $del = 0;

        if (!class_exists('Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $del;
        }

        $res = HL\HighloadBlockLangTable::getList(array(
            'filter' => array('ID' => $hlblockId)
        ));


        while ($row = $res->fetch()) {
            HL\HighloadBlockLangTable::delete($row['ID']);
            $del++;
        }

        return $del;
    }

    protected function addHblockLangs($hlblockId, $lang = array()) {
        $add = 0;

        if (!class_exists('Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $add;
        }

        foreach ($lang as $lid => $item) {
            if (!empty($item['NAME'])) {
                HL\HighloadBlockLangTable::add(array(
                    'ID' => $hlblockId,
                    'LID' => $lid,
                    'NAME' => $item['NAME']
                ));

                $add++;
            }
        }

        return $add;
    }

    protected function replaceHblockLangs($hlblockId, $lang = array()) {
        if (!empty($lang) && is_array($lang)) {
            $this->deleteHblockLangs($hlblockId);
            $this->addHblockLangs($hlblockId, $lang);
        }
    }

}