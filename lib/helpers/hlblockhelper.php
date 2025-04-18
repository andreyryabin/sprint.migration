<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Highloadblock\HighloadBlockLangTable;
use Bitrix\Highloadblock\HighloadBlockRightsTable;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\Entity\DataManager;
use Bitrix\Main\Entity\ExpressionField;
use CTask;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class HlblockHelper extends Helper
{
    /**
     * HlblockHelper constructor.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->checkModules(['highloadblock']);
    }

    /**
     * Получает список highload-блоков
     *
     * @param array $filter
     *
     * @return array
     * @throws HelperException
     */
    public function getHlblocks($filter = [])
    {
        $result = [];
        try {
            $dbres = HighloadBlockTable::getList(
                [
                    'select' => ['*'],
                    'filter' => $filter,
                ]
            );
            while ($hlblock = $dbres->fetch()) {
                $result[] = $this->prepareHlblock($hlblock);
            }
        } catch (Exception $e) {
            throw new HelperException($e);
        }
        return $result;
    }

    /**
     * Получает список highload-блоков
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param array $filter
     *
     * @return array
     * @throws HelperException
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
     *
     * @param $hlblockName int|string|array - id, имя или фильтр
     *
     * @return array
     * @throws HelperException
     */
    public function getFields($hlblockName)
    {
        $entityHelper = new UserTypeEntityHelper();
        return $entityHelper->getUserTypeEntities(
            $this->getEntityId($hlblockName)
        );
    }

    /**
     * @throws HelperException
     */
    public function getField($hlblockName, $fieldName): array
    {
        $entityHelper = new UserTypeEntityHelper();
        $field = $entityHelper->getUserTypeEntity(
            $this->getEntityId($hlblockName),
            $fieldName
        );

        if (!empty($field)) {
            return $field;
        }
        throw new HelperException(Locale::getMessage('ERR_HLBLOCK_FIELD_NOT_FOUND'));
    }

    /**
     * @param $hlblockName
     * @param $field
     *
     * @return string|void
     * @throws HelperException
     */
    public function getFieldUid($hlblockName, $field)
    {
        $entityHelper = new UserTypeEntityHelper();

        if (!is_array($field)) {
            //на вход пришел id или название поля
            if (is_numeric($field)) {
                $field = $entityHelper->getUserTypeEntityById($field);
            } else {
                $field = $entityHelper->getUserTypeEntity(
                    $this->getEntityId($hlblockName),
                    $field
                );
            }
        }

        if (!empty($field['FIELD_NAME'])) {
            return $field['FIELD_NAME'];
        }
        throw new HelperException(Locale::getMessage('ERR_HLBLOCK_FIELD_NOT_FOUND'));
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     *
     * @return mixed
     * @throws HelperException
     */
    public function getFieldType($hlblockName, $fieldName)
    {
        $field = $this->getField($hlblockName, $fieldName);
        return $field['USER_TYPE_ID'];
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     *
     * @return bool
     * @throws HelperException
     */
    public function isFieldMultiple($hlblockName, $fieldName)
    {
        $field = $this->getField($hlblockName, $fieldName);
        return ($field['MULTIPLE'] == 'Y');
    }

    /**
     * @param $hlblockName
     * @param $fieldName
     * @param $xmlId
     *
     * @return mixed|string
     * @throws HelperException
     */
    public function getFieldEnumIdByXmlId($hlblockName, $fieldName, $xmlId)
    {
        $field = $this->getField($hlblockName, $fieldName);
        if (empty($field['ENUM_VALUES']) || !is_array($field['ENUM_VALUES'])) {
            return '';
        }

        foreach ($field['ENUM_VALUES'] as $val) {
            if ($val['XML_ID'] == $xmlId) {
                return $val['ID'];
            }
        }

        return '';
    }

    /**
     * @throws HelperException
     */
    public function getFieldEnumXmlIdById($hlblockName, $fieldName, $valueId)
    {
        $field = $this->getField($hlblockName, $fieldName);
        if (empty($field['ENUM_VALUES']) || !is_array($field['ENUM_VALUES'])) {
            return '';
        }

        foreach ($field['ENUM_VALUES'] as $val) {
            if ($val['ID'] == $valueId) {
                return $val['XML_ID'];
            }
        }
        return '';
    }

    /**
     * @throws HelperException
     */
    public function getFieldEnumXmlIdsByIds($hlblockName, $fieldName, $valueIds): array
    {
        $valueIds = is_array($valueIds) ? $valueIds : [$valueIds];

        $field = $this->getField($hlblockName, $fieldName);
        if (empty($field['ENUM_VALUES']) || !is_array($field['ENUM_VALUES'])) {
            return [];
        }

        $xmlIds = [];
        foreach ($field['ENUM_VALUES'] as $val) {
            if (in_array($val['ID'], $valueIds)) {
                $xmlIds[] = $val['XML_ID'];
            }
        }
        return $xmlIds;
    }

    /**
     * @param $hlblockName
     * @param $fieldUid
     *
     * @return int
     * @throws HelperException
     */
    public function getFieldIdByUid($hlblockName, $fieldUid)
    {
        $fieldId = 0;

        if (empty($fieldUid)) {
            return $fieldId;
        }

        if (is_numeric($fieldUid)) {
            return $fieldUid;
        }

        $field = $this->getField($hlblockName, $fieldUid);

        return ($field) ? (int)$field['ID'] : 0;
    }

    /**
     * @param $hlblockName
     *
     * @return string
     * @throws HelperException
     */
    public function getEntityId($hlblockName)
    {
        $hlblockId = is_numeric($hlblockName) ? $hlblockName : $this->getHlblockId($hlblockName);
        return 'HLBLOCK_' . $hlblockId;
    }

    /**
     * Сохраняет поле highload-блока
     * Создаст если не было, обновит если существует и отличается
     *
     * @param       $hlblockName int|string|array - id, имя или фильтр
     * @param array $field
     *
     * @return bool|int|mixed
     * @throws HelperException
     */
    public function saveField($hlblockName, $field = [])
    {
        $field['ENTITY_ID'] = $this->getEntityId($hlblockName);

        $entityHelper = new UserTypeEntityHelper();
        return $entityHelper->saveUserTypeEntity($field);
    }

    /**
     * Сохраняет highload-блок
     * Создаст если не было, обновит если существует и отличается
     *
     * @param array $fields
     *
     * @return bool|int|mixed
     * @throws HelperException
     */
    public function saveHlblock($fields)
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        $exists = $this->getHlblock($fields['NAME']);
        $fields = $this->prepareExportHlblock($fields);

        if (empty($exists)) {
            $ok = $this->addHlblock($fields);

            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'HLBLOCK_CREATED',
                    [
                        '#NAME#' => $fields['NAME'],
                    ]
                )
            );

            return $ok;
        }

        $exportExists = $this->prepareExportHlblock($exists);

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->updateHlblock($exists['ID'], $fields);
            $this->outNoticeIf(
                $ok,
                Locale::getMessage(
                    'HLBLOCK_UPDATED',
                    [
                        '#NAME#' => $fields['NAME'],
                    ]
                )
            );

            $this->outDiffIf($ok, $exportExists, $fields);
            return $ok;
        }

        return $exists['ID'];
    }

    /**
     * Удаляет поле highload-блока
     *
     * @param $hlblockName
     * @param $fieldName
     *
     * @return bool
     * @throws HelperException
     */
    public function deleteField($hlblockName, $fieldName)
    {
        $entityHelper = new UserTypeEntityHelper();
        return $entityHelper->deleteUserTypeEntity(
            $this->getEntityId($hlblockName),
            $fieldName
        );
    }

    /**
     * Получает список полей highload-блока
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param $hlblockName
     *
     * @return array
     * @throws HelperException
     */
    public function exportFields($hlblockName)
    {
        $entityHelper = new UserTypeEntityHelper();
        $fields = $entityHelper->exportUserTypeEntities(
            $this->getEntityId($hlblockName)
        );

        foreach ($fields as $index => $field) {
            unset($field['ENTITY_ID']);
            $fields[$index] = $field;
        }

        return $fields;
    }

    /**
     * Получает highload-блок
     * Данные подготовлены для экспорта в миграцию или схему
     *
     * @param $hlblockName
     *
     * @return mixed
     * @throws HelperException
     */
    public function exportHlblock($hlblockName)
    {
        return $this->prepareExportHlblock(
            $this->getHlblock($hlblockName)
        );
    }

    /**
     * Получает highload-блок
     *
     * @param $hlblockName - id, имя или фильтр
     *
     * @return array|false
     * @throws HelperException
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

        try {
            $hlblock = HighloadBlockTable::getRow([
                'select' => ['*'],
                'filter' => $filter,
            ]);

            return $this->prepareHlblock($hlblock);
        } catch (Exception $e) {
            throw new HelperException($e->getMessage());
        }
    }

    /**
     * @param $hlblockName
     *
     * @return array|void
     * @throws HelperException
     */
    public function getHlblockIfExists($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        if ($item && isset($item['ID'])) {
            return $item;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_HLBLOCK_NOT_FOUND',
                ['#HLBLOCK#' => is_array($hlblockName) ? var_export($hlblockName, true) : $hlblockName]
            )
        );
    }

    /**
     * Получает highload-блок, бросает исключение если его не существует
     *
     * @param $hlblockName - id, имя или фильтр
     *
     * @return int|void
     * @throws HelperException
     */
    public function getHlblockIdIfExists($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        if ($item && isset($item['ID'])) {
            return $item['ID'];
        }

        if (is_array($hlblockName)) {
            $hlblockUid = var_export($hlblockName, true);
        } else {
            $hlblockUid = $hlblockName;
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_HLBLOCK_NOT_FOUND',
                ['#HLBLOCK#' => $hlblockUid]
            )
        );
    }

    /**
     * Получает id highload-блока
     *
     * @param $hlblockName - id, имя или фильтр
     *
     * @return int|mixed
     * @throws HelperException
     */
    public function getHlblockId($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        return ($item && isset($item['ID'])) ? $item['ID'] : 0;
    }

    public function getHlblockTableName($hlblockName)
    {
        $item = $this->getHlblock($hlblockName);
        return ($item && isset($item['TABLE_NAME'])) ? $item['TABLE_NAME'] : '';
    }

    /**
     * Добавляет highload-блок
     *
     * @param array $fields
     *
     * @return int|void
     * @throws HelperException
     */
    public function addHlblock($fields)
    {
        $this->checkRequiredKeys($fields, ['NAME', 'TABLE_NAME']);
        $fields['NAME'] = ucfirst($fields['NAME']);

        $lang = [];
        if (isset($fields['LANG'])) {
            $lang = $fields['LANG'];
            unset($fields['LANG']);
        }

        try {
            $result = HighloadBlockTable::add($fields);
            if ($result->isSuccess()) {
                $this->replaceHblockLangs($result->getId(), $lang);
                return $result->getId();
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Добавляет highload-блок, если его не существует
     *
     * @param array $fields
     *
     * @return int|mixed
     * @throws HelperException
     */
    public function addHlblockIfNotExists($fields)
    {
        $this->checkRequiredKeys($fields, ['NAME']);

        $item = $this->getHlblock($fields['NAME']);
        if ($item) {
            return $item['ID'];
        }

        return $this->addHlblock($fields);
    }

    /**
     * Обновляет highload-блок
     *
     * @param $hlblockId
     * @param $fields
     *
     * @return int|void
     * @throws HelperException
     */
    public function updateHlblock($hlblockId, $fields)
    {
        $lang = [];
        if (isset($fields['LANG'])) {
            $lang = $fields['LANG'];
            unset($fields['LANG']);
        }

        try {
            $result = HighloadBlockTable::update($hlblockId, $fields);

            if ($result->isSuccess()) {
                $this->replaceHblockLangs($hlblockId, $lang);
                return $hlblockId;
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Обновляет highload-блок, если существует
     *
     * @param $hlblockName
     * @param $fields
     *
     * @return bool|int
     * @throws HelperException
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
     *
     * @param $hlblockId
     *
     * @return bool|void
     * @throws HelperException
     */
    public function deleteHlblock($hlblockId)
    {
        try {
            $result = HighloadBlockTable::delete($hlblockId);
            if ($result->isSuccess()) {
                return true;
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Удаляет highload-блок, если существует
     *
     * @param $hlblockName
     *
     * @return bool
     * @throws HelperException
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
     * @param $hlblockName
     * @param $fields
     *
     * @return int|void
     * @throws HelperException
     */
    public function addElement($hlblockName, $fields)
    {
        $dataManager = $this->getDataManager($hlblockName);

        try {
            $result = $dataManager::add($fields);

            if ($result->isSuccess()) {
                return $result->getId();
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param $hlblockName
     * @param $elementId
     * @param $fields
     *
     * @return int|void
     * @throws HelperException
     */
    public function updateElement($hlblockName, $elementId, $fields)
    {
        $dataManager = $this->getDataManager($hlblockName);

        try {
            $result = $dataManager::update($elementId, $fields);

            if ($result->isSuccess()) {
                return $result->getId();
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function deleteElement($hlblockName, $elementId)
    {
        $dataManager = $this->getDataManager($hlblockName);
        try {
            $result = $dataManager::delete($elementId);

            if ($result->isSuccess()) {
                return true;
            }

            throw new HelperException(implode(PHP_EOL, $result->getErrorMessages()));
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function saveElementByXmlId($hlblockName, $fields)
    {
        $this->checkRequiredKeys($fields, ['UF_XML_ID']);

        $id = $this->getElementIdByXmlId($hlblockName, $fields['UF_XML_ID']);

        if ($id) {
            return $this->updateElement($hlblockName, $id, $fields);
        }

        return $this->addElement($hlblockName, $fields);
    }

    public function deleteElementByXmlId($hlblockName, $xmlId)
    {
        if (!empty($xmlId)) {
            $id = $this->getElementIdByXmlId($hlblockName, $xmlId);
            if ($id) {
                return $this->deleteElement($hlblockName, $id);
            }
        }
        return false;
    }

    /**
     * Получает права доступа к highload-блоку для групп
     * возвращает массив вида [$groupId => $letter]
     *
     * @param $hlblockId
     *
     * @return array
     */
    public function getGroupPermissions($hlblockId)
    {
        $permissions = [];
        $rights = $this->getGroupRights($hlblockId);
        foreach ($rights as $right) {
            $permissions[$right['GROUP_ID']] = $right['LETTER'];
        }
        return $permissions;
    }

    /**
     * @param $hlblockId
     *
     * @return array
     * @throws HelperException
     */
    public function exportGroupPermissions($hlblockId)
    {
        $groupHelper = new UserGroupHelper();
        $permissions = $this->getGroupPermissions($hlblockId);

        $result = [];
        foreach ($permissions as $groupId => $letter) {
            $groupCode = $groupHelper->getGroupCode($groupId);
            $groupCode = !empty($groupCode) ? $groupCode : $groupId;
            $result[$groupCode] = $letter;
        }

        return $result;
    }

    public function saveGroupPermissions($hlblockId, $permissions = [])
    {
        $groupHelper = new UserGroupHelper();

        $result = [];
        foreach ($permissions as $groupCode => $letter) {
            $groupId = is_numeric($groupCode) ? $groupCode : $groupHelper->getGroupId($groupCode);
            $result[$groupId] = $letter;
        }

        $this->setGroupPermissions($hlblockId, $result);
    }

    /**
     * Устанавливает права доступа к highload-блоку для групп
     * предыдущие права сбрасываются
     * принимает массив вида [$groupId => $letter]
     *
     * @param int $hlblockId
     * @param array $permissions
     *
     * @return bool
     * @throws HelperException
     */
    public function setGroupPermissions($hlblockId, $permissions = [])
    {
        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockRightsTable')) {
            return false;
        }

        $rights = $this->getGroupRights($hlblockId);

        try {
            foreach ($rights as $right) {
                HighloadBlockRightsTable::delete($right['ID']);
            }

            foreach ($permissions as $groupId => $letter) {
                $taskId = CTask::GetIdByLetter($letter, 'highloadblock');

                if (!empty($taskId)) {
                    HighloadBlockRightsTable::add(
                        [
                            'HL_ID' => $hlblockId,
                            'TASK_ID' => $taskId,
                            'ACCESS_CODE' => 'G' . $groupId,
                        ]
                    );
                }
            }
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }

        return true;
    }

    /**
     * @param $hlblockName
     *
     * @return DataManager|void
     * @throws HelperException
     */
    public function getDataManager($hlblockName)
    {
        try {
            $hlblock = $this->getHlblockIfExists($hlblockName);
            $entity = HighloadBlockTable::compileEntity($hlblock);
            return $entity->getDataClass();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param       $hlblockName
     * @param array $params
     *
     * @return array|void
     * @throws HelperException
     */
    public function getElements($hlblockName, $params = [])
    {
        $dataManager = $this->getDataManager($hlblockName);
        try {
            return $dataManager::getList($params)->fetchAll();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function getElement($hlblockName, array $filter)
    {
        $dataManager = $this->getDataManager($hlblockName);
        try {
            return $dataManager::getList([
                'filter' => $filter,
                'offset' => 0,
                'limit' => 1,
            ])->fetch();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws HelperException
     */
    public function getElementId($hlblockName, array $filter): int
    {
        $item = $this->getElement($hlblockName, $filter);
        return (int)($item['ID'] ?? 0);
    }

    /**
     * @param string $hlblockName
     * @param string $xmlId
     *
     * @return array|void
     * @throws HelperException
     */
    public function getElementByXmlId($hlblockName, $xmlId)
    {
        return $this->getElement($hlblockName, ['UF_XML_ID' => $xmlId]);
    }

    public function getElementIdByXmlId($hlblockName, $xmlId): int
    {
        return $this->getElementId($hlblockName, ['UF_XML_ID' => $xmlId]);
    }

    /**
     * @throws HelperException
     */
    public function getElementsCount($hlblockName, array $filter = [])
    {
        $dataManager = $this->getDataManager($hlblockName);
        try {
            $item = $dataManager::getList(
                [
                    'select' => ['CNT'],
                    'filter' => $filter,
                    'runtime' => [
                        new ExpressionField('CNT', 'COUNT(*)'),
                    ],
                ]
            )->fetch();

            return ($item) ? $item['CNT'] : 0;
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param        $hlblock
     *
     * @return string|void
     * @throws HelperException
     */
    public function getHlblockUid($hlblock)
    {
        if (!is_array($hlblock)) {
            // если хайлоадблок не найден, надо показать что искали
            $getHlblock = $this->getHlblock($hlblock);

            if (false === $getHlblock) {
                throw new HelperException(
                    Locale::getMessage(
                        'ERR_HLBLOCK_NOT_FOUND',
                        ['#HLBLOCK#' => $hlblock]
                    )
                );
            }

            $hlblock = $getHlblock;
        }

        if (!empty($hlblock['NAME'])) {
            return $hlblock['NAME'];
        }

        throw new HelperException(
            Locale::getMessage(
                'ERR_HLBLOCK_NOT_FOUND',
                ['#HLBLOCK#' => is_array($hlblock) ? var_export($hlblock, true) : $hlblock]
            )
        );
    }

    /**
     * @param $hlblockUid
     *
     * @return int
     * @throws HelperException
     */
    public function getHlblockIdByUid($hlblockUid)
    {
        if (empty($hlblockUid)) {
            return 0;
        }

        return $this->getHlblockIdIfExists($hlblockUid);
    }

    /**
     * @param $item
     *
     * @return mixed
     */
    protected function prepareHlblock($item)
    {
        if (empty($item['ID'])) {
            return $item;
        }

        $langs = $this->getHblockLangs($item['ID']);
        if (!empty($langs)) {
            $item['LANG'] = $langs;
        }

        return $item;
    }

    /**
     * @param int $hlblockId
     *
     * @return array
     * @throws HelperException
     */
    protected function getGroupRights($hlblockId)
    {
        $result = [];
        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockRightsTable')) {
            return $result;
        }

        try {
            $items = HighloadBlockRightsTable::getList(
                [
                    'filter' => [
                        'HL_ID' => $hlblockId,
                    ],
                ]
            )->fetchAll();
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }

        foreach ($items as $item) {
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

    protected function prepareExportHlblock($item)
    {
        if (empty($item)) {
            return $item;
        }

        unset($item['ID']);

        return $item;
    }

    /**
     * @param int $hlblockId
     *
     * @return array
     * @throws HelperException
     */
    protected function getHblockLangs($hlblockId)
    {
        $result = [];

        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $result;
        }

        try {
            $dbres = HighloadBlockLangTable::getList(
                [
                    'filter' => ['ID' => $hlblockId],
                ]
            );

            while ($item = $dbres->fetch()) {
                $result[$item['LID']] = [
                    'NAME' => $item['NAME'],
                ];
            }
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }

        return $result;
    }

    /**
     * @param int $hlblockId
     *
     * @return int
     * @throws Exception
     */
    protected function deleteHblockLangs($hlblockId)
    {
        $del = 0;

        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $del;
        }

        try {
            $items = HighloadBlockLangTable::getList(
                [
                    'filter' => ['ID' => $hlblockId],
                ]
            )->fetchAll();
        } catch (Exception $e) {
            $items = [];
        }

        foreach ($items as $item) {
            HighloadBlockLangTable::delete(['ID' => $item['ID'], 'LID' => $item['LID']]);
            $del++;
        }

        return $del;
    }

    /**
     * @param int $hlblockId
     * @param array $lang
     *
     * @return int
     * @throws Exception
     */
    protected function addHblockLangs($hlblockId, $lang = [])
    {
        $add = 0;

        if (!class_exists('\Bitrix\Highloadblock\HighloadBlockLangTable')) {
            return $add;
        }

        foreach ($lang as $lid => $item) {
            if (!empty($item['NAME'])) {
                HighloadBlockLangTable::add(
                    [
                        'ID' => $hlblockId,
                        'LID' => $lid,
                        'NAME' => $item['NAME'],
                    ]
                );

                $add++;
            }
        }

        return $add;
    }

    /**
     * @param int $hlblockId
     * @param array $lang
     *
     * @throws Exception
     */
    protected function replaceHblockLangs($hlblockId, $lang = [])
    {
        if (!empty($lang) && is_array($lang)) {
            $this->deleteHblockLangs($hlblockId);
            $this->addHblockLangs($hlblockId, $lang);
        }
    }
}
