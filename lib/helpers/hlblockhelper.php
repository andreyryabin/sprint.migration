<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Highloadblock as HL;
use Bitrix\Main\ArgumentException;
use Bitrix\Main\SystemException;
use CTask;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;


class HlblockHelper extends Helper
{

    public function __construct()
    {
        $this->checkModules(['highloadblock']);
    }

    /**
     * Получает список highload-блоков
     * @param array $filter
     * @throws ArgumentException
     * @return array
     */
    public function getHlblocks($filter = [])
    {
        $dbres = HL\HighloadBlockTable::getList(
            [
                'select' => ['*'],
                'filter' => $filter,
            ]
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
     * @throws ArgumentException
     * @return array
     */
    public function exportHlblocks($filter = [])
    {
        $items = $this->getHlblocks($filter);

        $export = [];
        foreach ($items as $item) {
            $export[] = $this->prepareExportHlblock($item);
        }

        return $export;
    }

    /**
     * Получает список полей highload-блока
     * @param $hlblockName int|string|array - id, имя или фильтр
     * @throws ArgumentException
     * @return array
     */
    public function getFields($hlblockName)
    {
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
     * @throws ArgumentException
     * @throws HelperException
     * @return bool|int|mixed
     */
    public function saveField($hlblockName, $field = [])
    {
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
     * @throws ArgumentException
     * @throws SystemException
     * @throws HelperException
     * @return bool|int|mixed
     */
    public function saveHlblock($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['NAME']);

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
     * @throws ArgumentException
     * @throws HelperException
     * @return bool
     */
    public function deleteField($hlblockName, $fieldName)
    {
        $hlblockId = is_numeric($hlblockName) ? $hlblockName : $this->getHlblockId($hlblockName);

        $entityHelper = new UserTypeEntityHelper();
        $entityHelper->setMode($this);
        return $entityHelper->deleteUserTypeEntity('HLBLOCK_' . $hlblockId, $fieldName);
    }

    /**
     * Получает список полей highload-блока
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $hlblockName
     * @throws ArgumentException
     * @return array
     */
    public function exportFields($hlblockName)
    {
        $fields = $this->getFields($hlblockName);
        $export = [];
        foreach ($fields as $field) {
            $export[] = $this->prepareExportHlblockField($field);
        }

        return $export;
    }

    /**
     * Получает highload-блок
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $hlblockName
     * @throws ArgumentException
     * @return mixed
     */
    public function exportHlblock($hlblockName)
    {
        return $this->prepareExportHlblock(
            $this->getHlblock($hlblockName)
        );
    }

    /**
     * Получает highload-блок
     * @param $hlblockName - id, имя или фильтр
     * @throws ArgumentException
     * @return array|false
     */
    public function getHlblock($hlblockName)
    {
        if (is_array($hlblockName)) {
            $filter = $hlblockName;
        } elseif (is_numeric($hlblockName)) {
            $filter = ['ID' => $hlblockName];
        } else {
            $filter = ['NAME' => $hlblockName];
        }

        $result = HL\HighloadBlockTable::getList(
            [
                'select' => ['*'],
                'filter' => $filter,
            ]
        );

        $hlblock = $result->fetch();
        if ($hlblock) {
            $hlblock['LANG'] = $this->getHblockLangs($hlblock['ID']);
        }

        return $hlblock;
    }

    /**
     * @param $hlblockName
     * @throws ArgumentException
     * @throws HelperException
     * @return array|false
     */
    public function getHlblockIfExists($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        if ($item && isset($item['ID'])) {
            return $item;
        }

        $this->throwException(__METHOD__, "hlblock not found");
    }

    /**
     * Получает highload-блок, бросает исключение если его не существует
     * @param $hlblockName - id, имя или фильтр
     * @throws ArgumentException
     * @throws HelperException
     * @return mixed
     */
    public function getHlblockIdIfExists($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        if ($item && isset($item['ID'])) {
            return $item['ID'];
        }

        $this->throwException(__METHOD__, "hlblock id not found");
    }

    /**
     * Получает id highload-блока
     * @param $hlblockName - id, имя или фильтр
     * @throws ArgumentException
     * @return int|mixed
     */
    public function getHlblockId($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    /**
     * Добавляет highload-блок
     * @param $fields , обязательные параметры - название сущности, название таблицы в БД
     * @throws SystemException
     * @throws HelperException
     * @return int
     */
    public function addHlblock($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['NAME', 'TABLE_NAME']);

        $lang = [];
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
     * @throws ArgumentException
     * @throws SystemException
     * @throws HelperException
     * @return int|mixed
     */
    public function addHlblockIfNotExists($fields)
    {
        $this->checkRequiredKeys(__METHOD__, $fields, ['NAME']);

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
     * @throws HelperException
     * @return mixed
     */
    public function updateHlblock($hlblockId, $fields)
    {
        $lang = [];
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
     * @throws ArgumentException
     * @throws HelperException
     * @return bool|mixed
     */
    public function updateHlblockIfExists($hlblockName, $fields)
    {
        $item = $this->getHlblock($hlblockName);
        if (!$item) {
            return false;
        }

        return $this->updateHlblock($item['ID'], $fields);
    }

    /**
     * Удаляет highload-блок
     * @param $hlblockId
     * @throws HelperException
     * @return bool
     */
    public function deleteHlblock($hlblockId)
    {
        $result = HL\HighloadBlockTable::delete($hlblockId);
        if ($result->isSuccess()) {
            return true;
        }

        $this->throwException(__METHOD__, implode(', ', $result->getErrors()));
    }

    /**
     * Удаляет highload-блок, если существует
     * @param $hlblockName
     * @throws ArgumentException
     * @throws HelperException
     * @return bool
     */
    public function deleteHlblockIfExists($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        if (!$item) {
            return false;
        }

        return $this->deleteHlblock($item['ID']);
    }

    /**
     * Получает права доступа к highload-блоку для групп
     * возвращает массив вида [$groupId => $letter]
     *
     * @param $hlblockId
     * @return array
     */
    public function getGroupPermissions($hlblockId)
    {
        $result = [];
        $rights = $this->getGroupRights($hlblockId);
        foreach ($rights as $right) {
            $result[$right['GROUP_ID']] = $right['LETTER'];
        }

        return $result;
    }

    /**
     * Устанавливает права доступа к highload-блоку для групп
     * предыдущие права сбрасываются
     * принимает массив вида [$groupId => $letter]
     *
     * @param $hlblockId
     * @param array $permissions
     * @throws Exception
     */
    public function setGroupPermissions($hlblockId, $permissions = [])
    {
        $rights = $this->getGroupRights($hlblockId);
        foreach ($rights as $right) {
            HL\HighloadBlockRightsTable::delete($right['ID']);
        }

        foreach ($permissions as $groupId => $letter) {
            $taskId = CTask::GetIdByLetter($letter, 'highloadblock');

            if (empty($taskId)) {
                continue;
            }

            HL\HighloadBlockRightsTable::add(
                [
                    'HL_ID' => $hlblockId,
                    'TASK_ID' => $taskId,
                    'ACCESS_CODE' => 'G' . $groupId,
                ]
            );
        }
    }

    protected function getGroupRights($hlblockId)
    {
        $dbres = HL\HighloadBlockRightsTable::getList(
            [
                'filter' => [
                    'HL_ID' => $hlblockId,
                ],
            ]
        );
        $result = [];

        while ($item = $dbres->fetch()) {
            if (strpos($item['ACCESS_CODE'], 'G') !== 0) {
                continue;
            }

            $groupId = (int)substr($item['ACCESS_CODE'], 1);
            if (empty($groupId)) {
                continue;
            }

            $letter = CTask::GetLetter($item['TASK_ID']);
            if (empty($letter)) {
                continue;
            }

            $item['LETTER'] = $letter;
            $item['GROUP_ID'] = $groupId;

            $result[] = $item;
        }

        return $result;
    }

    protected function prepareExportHlblockField($item)
    {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);
        unset($item['ENTITY_ID']);

        return $item;
    }

    protected function prepareExportHlblock($item)
    {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);

        return $item;
    }

    protected function getHblockLangs($hlblockId)
    {
        $result = [];

        if (!class_exists('Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $result;
        }

        $dbres = HL\HighloadBlockLangTable::getList([
            'filter' => ['ID' => $hlblockId],
        ]);


        while ($item = $dbres->fetch()) {
            $result[$item['LID']] = [
                'NAME' => $item['NAME'],
            ];
        }

        return $result;
    }

    protected function deleteHblockLangs($hlblockId)
    {
        $del = 0;

        if (!class_exists('Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $del;
        }

        $res = HL\HighloadBlockLangTable::getList([
            'filter' => ['ID' => $hlblockId],
        ]);


        while ($row = $res->fetch()) {
            HL\HighloadBlockLangTable::delete($row['ID']);
            $del++;
        }

        return $del;
    }

    protected function addHblockLangs($hlblockId, $lang = [])
    {
        $add = 0;

        if (!class_exists('Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $add;
        }

        foreach ($lang as $lid => $item) {
            if (!empty($item['NAME'])) {
                HL\HighloadBlockLangTable::add([
                    'ID' => $hlblockId,
                    'LID' => $lid,
                    'NAME' => $item['NAME'],
                ]);

                $add++;
            }
        }

        return $add;
    }

    protected function replaceHblockLangs($hlblockId, $lang = [])
    {
        if (!empty($lang) && is_array($lang)) {
            $this->deleteHblockLangs($hlblockId);
            $this->addHblockLangs($hlblockId, $lang);
        }
    }

}