<?php

namespace Sprint\Migration\Helpers;

use CMain;
use CUserFieldEnum;
use CUserTypeEntity;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;

class UserTypeEntityHelper extends Helper
{

    /**
     * Добавляет пользовательские поля к объекту
     * @param $entityId
     * @param array $fields
     * @throws HelperException
     */
    public function addUserTypeEntitiesIfNotExists($entityId, array $fields)
    {
        foreach ($fields as $field) {
            $this->addUserTypeEntityIfNotExists($entityId, $field["FIELD_NAME"], $field);
        }
    }

    /**
     * Удаляет пользовательские поля у объекта
     * @param $entityId
     * @param array $fields
     * @throws HelperException
     */
    public function deleteUserTypeEntitiesIfExists($entityId, array $fields)
    {
        foreach ($fields as $fieldName) {
            $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
        }
    }

    /**
     * Добавляет пользовательское поле к объекту если его не существует
     * @param $entityId
     * @param $fieldName
     * @param $fields
     * @throws HelperException
     * @return int
     */
    public function addUserTypeEntityIfNotExists($entityId, $fieldName, $fields)
    {
        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if ($item) {
            return $item['ID'];
        }

        return $this->addUserTypeEntity($entityId, $fieldName, $fields);
    }

    /**
     * Добавляет пользовательское поле к объекту
     * @param $entityId
     * @param $fieldName
     * @param $fields
     * @throws HelperException
     * @return int|void
     */
    public function addUserTypeEntity($entityId, $fieldName, $fields)
    {

        $default = [
            "ENTITY_ID" => '',
            "FIELD_NAME" => '',
            "USER_TYPE_ID" => '',
            "XML_ID" => '',
            "SORT" => 500,
            "MULTIPLE" => 'N',
            "MANDATORY" => 'N',
            "SHOW_FILTER" => 'I',
            "SHOW_IN_LIST" => '',
            "EDIT_IN_LIST" => '',
            "IS_SEARCHABLE" => '',
            "SETTINGS" => [],
            "EDIT_FORM_LABEL" => ['ru' => '', 'en' => ''],
            "LIST_COLUMN_LABEL" => ['ru' => '', 'en' => ''],
            "LIST_FILTER_LABEL" => ['ru' => '', 'en' => ''],
            "ERROR_MESSAGE" => '',
            "HELP_MESSAGE" => '',
        ];

        $fields = array_replace_recursive($default, $fields);
        $fields['FIELD_NAME'] = $fieldName;
        $fields['ENTITY_ID'] = $entityId;

        $enums = [];
        if (isset($fields['ENUM_VALUES'])) {
            $enums = $fields['ENUM_VALUES'];
            unset($fields['ENUM_VALUES']);
        }

        $obUserField = new CUserTypeEntity;
        $userFieldId = $obUserField->Add($fields);

        $enumsCreated = true;
        if ($userFieldId && $fields['USER_TYPE_ID'] == 'enumeration') {
            $enumsCreated = $this->setUserTypeEntityEnumValues($userFieldId, $enums);
        }

        if ($userFieldId && $enumsCreated) {
            return $userFieldId;
        }

        /* @global $APPLICATION CMain */
        global $APPLICATION;
        if ($APPLICATION->GetException()) {
            $this->throwException(__METHOD__, $APPLICATION->GetException()->GetString());
        } else {
            $this->throwException(__METHOD__, 'UserType %s not added', $fieldName);
        }
    }

    /**
     * Обновление пользовательского поля у объекта
     * @param $fieldId
     * @param $fields
     * @throws HelperException
     * @return int|void
     */
    public function updateUserTypeEntity($fieldId, $fields)
    {
        $enums = [];
        if (isset($fields['ENUM_VALUES'])) {
            $enums = $fields['ENUM_VALUES'];
            unset($fields['ENUM_VALUES']);
        }

        unset($fields["ENTITY_ID"]);
        unset($fields["FIELD_NAME"]);
        unset($fields["MULTIPLE"]);

        $entity = new CUserTypeEntity;
        $userFieldUpdated = $entity->Update($fieldId, $fields);

        $enumsCreated = true;
        if ($userFieldUpdated && $fields['USER_TYPE_ID'] == 'enumeration') {
            $enumsCreated = $this->setUserTypeEntityEnumValues($fieldId, $enums);
        }

        if ($userFieldUpdated && $enumsCreated) {
            return $fieldId;
        }
        /* @global $APPLICATION CMain */
        global $APPLICATION;
        if ($APPLICATION->GetException()) {
            $this->throwException(__METHOD__, $APPLICATION->GetException()->GetString());
        } else {
            $this->throwException(__METHOD__, 'UserType %s not updated', $fieldId);
        }
    }

    /**
     * Обновление пользовательского поля у объекта если оно существует
     * @param $entityId
     * @param $fieldName
     * @param $fields
     * @throws HelperException
     * @return bool|mixed
     */
    public function updateUserTypeEntityIfExists($entityId, $fieldName, $fields)
    {
        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if (!$item) {
            return false;
        }

        return $this->updateUserTypeEntity($item['ID'], $fields);

    }

    /**
     * Получает пользовательские поля у объекта
     * @param bool $entityId
     * @return array
     */
    public function getUserTypeEntities($entityId = false)
    {
        if (!empty($entityId)) {
            $filter = is_array($entityId) ? $entityId : [
                'ENTITY_ID' => $entityId,
            ];
        } else {
            $filter = [];
        }


        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = CUserTypeEntity::GetList([], $filter);
        $result = [];
        while ($item = $dbres->Fetch()) {
            $result[] = $this->getUserTypeEntityById($item['ID']);

        }
        return $result;
    }

    /**
     * Получает пользовательское поле у объекта
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $fieldId
     * @throws HelperException
     * @return mixed
     */
    public function exportUserTypeEntity($fieldId)
    {
        $item = $this->getUserTypeEntityById($fieldId);
        return $this->prepareExportUserTypeEntity($item, true);
    }

    /**
     * Получает пользовательские поля у объекта
     * Данные подготовлены для экспорта в миграцию или схему
     * @param bool $entityId
     * @throws HelperException
     * @return array
     */
    public function exportUserTypeEntities($entityId = false)
    {
        $items = $this->getUserTypeEntities($entityId);
        $export = [];
        foreach ($items as $item) {
            $export[] = $this->prepareExportUserTypeEntity($item, true);
        }
        return $export;
    }

    /**
     * Получает пользовательское поле у объекта
     * @param $entityId
     * @param $fieldName
     * @return array|bool
     */
    public function getUserTypeEntity($entityId, $fieldName)
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $item = CUserTypeEntity::GetList([], [
            'ENTITY_ID' => $entityId,
            'FIELD_NAME' => $fieldName,
        ])->Fetch();

        return (!empty($item)) ? $this->getUserTypeEntityById($item['ID']) : false;
    }

    /**
     * Получает пользовательское поле у объекта
     * @param $fieldId
     * @return array|bool
     */
    public function getUserTypeEntityById($fieldId)
    {
        $item = CUserTypeEntity::GetByID($fieldId);
        if (empty($item)) {
            return false;
        }

        if ($item['USER_TYPE_ID'] == 'enumeration') {
            $item['ENUM_VALUES'] = $this->getEnumValues($fieldId);
        }

        return $item;
    }

    /**
     * Сохраняет значения списков для пользовательского поля
     * @param $fieldId
     * @param $newenums
     * @return bool
     */
    public function setUserTypeEntityEnumValues($fieldId, $newenums)
    {
        $newenums = is_array($newenums) ? $newenums : [];
        $oldenums = $this->getEnumValues($fieldId);

        $index = 0;

        $updates = [];
        foreach ($oldenums as $oldenum) {
            $newenum = $this->searchEnum($oldenum, $newenums);
            if ($newenum) {
                $updates[$oldenum['ID']] = $newenum;
            } else {
                $oldenum['DEL'] = 'Y';
                $updates[$oldenum['ID']] = $oldenum;
            }
        }

        foreach ($newenums as $newenum) {
            $oldenum = $this->searchEnum($newenum, $oldenums);
            if ($oldenum) {
                $updates[$oldenum['ID']] = $newenum;
            } else {
                $updates['n' . $index++] = $newenum;
            }
        }

        $obEnum = new CUserFieldEnum();
        return $obEnum->SetEnumValues($fieldId, $updates);

    }

    /**
     * Удаляет пользовательское поле у объекта если оно существует
     * @param $entityId
     * @param $fieldName
     * @throws HelperException
     * @return bool|void
     */
    public function deleteUserTypeEntityIfExists($entityId, $fieldName)
    {
        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if (empty($item)) {
            return false;
        }

        $entity = new CUserTypeEntity();
        if ($entity->Delete($item['ID'])) {
            return true;
        }

        $this->throwException(__METHOD__, 'UserType not deleted');
    }

    /**
     * Удаляет пользовательское поле у объекта
     * @param $entityId
     * @param $fieldName
     * @throws HelperException
     * @return bool
     */
    public function deleteUserTypeEntity($entityId, $fieldName)
    {
        return $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
    }

    /**
     * Декодирует название объекта в оригинальный вид
     * @param $entityId
     * @throws HelperException
     * @return string
     */
    public function revertEntityId($entityId)
    {
        if (0 === strpos($entityId, 'HLBLOCK_')) {
            $hlblockId = substr($entityId, 8);
            if (!is_numeric($hlblockId)) {
                $hlblockId = (new HlblockHelper())->getHlblockIdByUid($hlblockId);
            }
            return 'HLBLOCK_' . $hlblockId;
        }

        $matches = [];
        if (preg_match('/^IBLOCK_(.+)_SECTION$/', $entityId, $matches)) {
            $iblockId = $matches[1];
            if (!is_numeric($iblockId)) {
                $iblockId = (new IblockHelper())->getIblockIdByUid($iblockId);
            }
            return 'IBLOCK_' . $iblockId . '_SECTION';
        }

        return $entityId;
    }

    /**
     * Кодирует название объекта в вид удобный для экспорта в миграцию или схему
     * @param $entityId
     * @throws HelperException
     * @return string
     */
    public function transformEntityId($entityId)
    {
        if (0 === strpos($entityId, 'HLBLOCK_')) {
            $hlblockId = substr($entityId, 8);
            if (is_numeric($hlblockId)) {
                $hlblockId = (new HlblockHelper())->getHlblockUid($hlblockId);
            }
            return 'HLBLOCK_' . $hlblockId;
        }

        $matches = [];
        if (preg_match('/^IBLOCK_(.+)_SECTION$/', $entityId, $matches)) {
            $iblockId = $matches[1];
            if (is_numeric($iblockId)) {
                $iblockId = (new IblockHelper())->getIblockUid($iblockId);
            }
            return 'IBLOCK_' . $iblockId . '_SECTION';
        }

        return $entityId;
    }

    /**
     * Сохраняет пользовательское поле
     * Создаст если не было, обновит если существует и отличается
     * @param array $fields , обязательные параметры - название объекта, название поля
     * @throws HelperException
     * @return bool|int|mixed
     */
    public function saveUserTypeEntity($fields = [])
    {

        if (func_num_args() > 1) {
            /** @compability */
            list($entityId, $fieldName, $fields) = func_get_args();
            $fields['ENTITY_ID'] = $entityId;
            $fields['FIELD_NAME'] = $fieldName;
        }

        $this->checkRequiredKeys(__METHOD__, $fields, ['ENTITY_ID', 'FIELD_NAME']);

        $fields['ENTITY_ID'] = $this->revertEntityId(
            $fields['ENTITY_ID']
        );

        $exists = $this->getUserTypeEntity(
            $fields['ENTITY_ID'],
            $fields['FIELD_NAME']
        );

        $exportExists = $this->prepareExportUserTypeEntity($exists, false);
        $fields = $this->prepareExportUserTypeEntity($fields, false);

        if (empty($exists)) {
            $ok = $this->getMode('test') ? true : $this->addUserTypeEntity(
                $fields['ENTITY_ID'],
                $fields['FIELD_NAME'],
                $fields
            );

            $this->outNoticeIf($ok, 'Пользовательское поле %s: добавлено', $fields['FIELD_NAME']);
            return $ok;
        }

        unset($exportExists['MULTIPLE']);
        unset($fields['MULTIPLE']);

        if ($this->hasDiff($exportExists, $fields)) {
            $ok = $this->getMode('test') ? true : $this->updateUserTypeEntity($exists['ID'], $fields);
            $this->outNoticeIf($ok, 'Пользовательское поле %s: обновлено', $fields['FIELD_NAME']);
            $this->outDiffIf($ok, $exportExists, $fields);
            return $ok;
        }

        $ok = $this->getMode('test') ? true : $exists['ID'];
        if ($this->getMode('out_equal')) {
            $this->outIf($ok, 'Пользовательское поле %s: совпадает', $fields['FIELD_NAME']);
        }
        return $ok;

    }

    /**
     * @param $entity
     * @param bool $transformEntityId
     * @throws HelperException
     * @return mixed
     */
    protected function prepareExportUserTypeEntity($entity, $transformEntityId = false)
    {
        if (empty($entity)) {
            return $entity;
        }

        if (!empty($entity['ENUM_VALUES']) && is_array($entity['ENUM_VALUES'])) {
            $exportValues = [];
            foreach ($entity['ENUM_VALUES'] as $item) {
                $exportValues[] = [
                    'VALUE' => $item['VALUE'],
                    'DEF' => $item['DEF'],
                    'SORT' => $item['SORT'],
                    'XML_ID' => $item['XML_ID'],
                ];
            }
            $entity['ENUM_VALUES'] = $exportValues;
        }

        if ($transformEntityId) {
            $entity['ENTITY_ID'] = $this->transformEntityId(
                $entity['ENTITY_ID']
            );
        }

        unset($entity['ID']);
        return $entity;
    }

    /**
     * @param $fieldId
     * @return array
     */
    protected function getEnumValues($fieldId)
    {
        $obEnum = new CUserFieldEnum;
        $dbres = $obEnum->GetList([], ["USER_FIELD_ID" => $fieldId]);
        return $this->fetchAll($dbres);
    }

    /**
     * @param $enum
     * @param array $haystack
     * @return bool|mixed
     */
    protected function searchEnum($enum, $haystack = [])
    {
        foreach ($haystack as $item) {
            if (!empty($item['XML_ID']) && $item['XML_ID'] == $enum['XML_ID']) {
                return $item;
            }
        }
        return false;
    }

}
