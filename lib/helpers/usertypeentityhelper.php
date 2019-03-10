<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class UserTypeEntityHelper extends Helper
{

    /**
     * Добавляет пользовательские поля к объекту
     * @param $entityId
     * @param array $fields
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addUserTypeEntitiesIfNotExists($entityId, array $fields) {
        foreach ($fields as $field) {
            $this->addUserTypeEntityIfNotExists($entityId, $field["FIELD_NAME"], $field);
        }
    }

    /**
     * Удаляет пользовательские поля у объекта
     * @param $entityId
     * @param array $fields
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function deleteUserTypeEntitiesIfExists($entityId, array $fields) {
        foreach ($fields as $fieldName) {
            $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
        }
    }

    /**
     * Добавляет пользовательское поле к объекту если его не существует
     * @param $entityId
     * @param $fieldName
     * @param $fields
     * @return int
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addUserTypeEntityIfNotExists($entityId, $fieldName, $fields) {
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
     * @return int
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function addUserTypeEntity($entityId, $fieldName, $fields) {

        $default = array(
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
            "SETTINGS" => array(),
            "EDIT_FORM_LABEL" => array('ru' => '', 'en' => ''),
            "LIST_COLUMN_LABEL" => array('ru' => '', 'en' => ''),
            "LIST_FILTER_LABEL" => array('ru' => '', 'en' => ''),
            "ERROR_MESSAGE" => '',
            "HELP_MESSAGE" => '',
        );

        $fields = array_replace_recursive($default, $fields);
        $fields['FIELD_NAME'] = $fieldName;
        $fields['ENTITY_ID'] = $entityId;

        $enums = array();
        if (isset($fields['ENUM_VALUES'])) {
            $enums = $fields['ENUM_VALUES'];
            unset($fields['ENUM_VALUES']);
        }

        $obUserField = new \CUserTypeEntity;
        $userFieldId = $obUserField->Add($fields);

        $enumsCreated = true;
        if ($userFieldId && $fields['USER_TYPE_ID'] == 'enumeration') {
            $enumsCreated = $this->setUserTypeEntityEnumValues($userFieldId, $enums);
        }

        if ($userFieldId && $enumsCreated) {
            return $userFieldId;
        }

        /* @global $APPLICATION \CMain */
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
     * @return mixed
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function updateUserTypeEntity($fieldId, $fields) {
        $enums = array();
        if (isset($fields['ENUM_VALUES'])) {
            $enums = $fields['ENUM_VALUES'];
            unset($fields['ENUM_VALUES']);
        }

        unset($fields["ENTITY_ID"]);
        unset($fields["FIELD_NAME"]);
        unset($fields["MULTIPLE"]);

        $entity = new \CUserTypeEntity;
        $userFieldUpdated = $entity->Update($fieldId, $fields);

        $enumsCreated = true;
        if ($userFieldUpdated && $fields['USER_TYPE_ID'] == 'enumeration') {
            $enumsCreated = $this->setUserTypeEntityEnumValues($fieldId, $enums);
        }

        if ($userFieldUpdated && $enumsCreated) {
            return $fieldId;
        }
        /* @global $APPLICATION \CMain */
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
     * @return bool|mixed
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function updateUserTypeEntityIfExists($entityId, $fieldName, $fields) {
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
    public function getUserTypeEntities($entityId = false) {
        if (!empty($entityId)) {
            $filter = is_array($entityId) ? $entityId : array(
                'ENTITY_ID' => $entityId
            );
        } else {
            $filter = array();
        }


        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = \CUserTypeEntity::GetList(array(), $filter);
        $result = array();
        while ($item = $dbres->Fetch()) {
            $result[] = $this->getUserTypeEntityById($item['ID']);

        }
        return $result;
    }

    /**
     * Получает пользовательское поле у объекта
     * Данные подготовлены для экспорта в миграцию или схему
     * @param $fieldId
     * @return mixed
     */
    public function exportUserTypeEntity($fieldId) {
        $item = $this->getUserTypeEntityById($fieldId);
        return $this->prepareExportUserTypeEntity($item, true);
    }

    /**
     * Получает пользовательские поля у объекта
     * Данные подготовлены для экспорта в миграцию или схему
     * @param bool $entityId
     * @return array
     */
    public function exportUserTypeEntities($entityId = false) {
        $items = $this->getUserTypeEntities($entityId);
        $export = array();
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
    public function getUserTypeEntity($entityId, $fieldName) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $item = \CUserTypeEntity::GetList(array(), array(
            'ENTITY_ID' => $entityId,
            'FIELD_NAME' => $fieldName
        ))->Fetch();

        return (!empty($item)) ? $this->getUserTypeEntityById($item['ID']) : false;
    }

    /**
     * Получает пользовательское поле у объекта
     * @param $fieldId
     * @return array|bool
     */
    public function getUserTypeEntityById($fieldId) {
        $item = \CUserTypeEntity::GetByID($fieldId);
        if (empty($item)) {
            return false;
        }

        if ($item['USER_TYPE_ID'] == 'enumeration') {
            $item['ENUM_VALUES'] = $this->getEnumValues($fieldId, false);
        }

        return $item;
    }

    /**
     * Сохраняет значения списков для пользовательского поля
     * @param $fieldId
     * @param $newenums
     * @return bool
     */
    public function setUserTypeEntityEnumValues($fieldId, $newenums) {
        $newenums = is_array($newenums) ? $newenums : array();
        $oldenums = $this->getEnumValues($fieldId, true);

        $index = 0;

        $updates = array();
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

        $obEnum = new \CUserFieldEnum();
        return $obEnum->SetEnumValues($fieldId, $updates);

    }

    /**
     * Удаляет пользовательское поле у объекта если оно существует
     * @param $entityId
     * @param $fieldName
     * @return bool
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function deleteUserTypeEntityIfExists($entityId, $fieldName) {
        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if (empty($item)) {
            return false;
        }

        $entity = new \CUserTypeEntity();
        if ($entity->Delete($item['ID'])) {
            return true;
        }

        $this->throwException(__METHOD__, 'UserType not deleted');
    }

    /**
     * Удаляет пользовательское поле у объекта
     * @param $entityId
     * @param $fieldName
     * @return bool
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function deleteUserTypeEntity($entityId, $fieldName) {
        return $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
    }

    /**
     * Декодирует название объекта в оригинальный вид
     * @param $entityId
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function revertEntityId($entityId) {
        if (0 === strpos($entityId, 'HLBLOCK_')) {
            $hlblockName = substr($entityId, 8);
            $hlhelper = new HlblockHelper();
            $hlblock = $hlhelper->getHlblock($hlblockName);
            if (empty($hlblock)) {
                $this->throwException(__METHOD__, '%s not found', $entityId);
            }

            $entityId = 'HLBLOCK_' . $hlblock['ID'];
        }

        return $entityId;
    }

    /**
     * Кодирует название объекта в вид удобный для экспорта в миграцию или схему
     * @param $entityId
     * @return string
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function transformEntityId($entityId) {
        if (0 === strpos($entityId, 'HLBLOCK_')) {
            $hlblockId = substr($entityId, 8);
            $hlhelper = new HlblockHelper();
            $hlblock = $hlhelper->getHlblock($hlblockId);
            if (empty($hlblock)) {
                $this->throwException(__METHOD__, '%s not found', $entityId);
            }
            $entityId = 'HLBLOCK_' . $hlblock['NAME'];
        }
        return $entityId;
    }

    /**
     * Сохраняет пользовательское поле
     * Создаст если не было, обновит если существует и отличается
     * @param array $fields , обязательные параметры - название объекта, название поля
     * @return bool|int|mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    public function saveUserTypeEntity($fields = array()) {

        if (func_num_args() > 1) {
            /** @compability */
            list($entityId, $fieldName, $fields) = func_get_args();
            $fields['ENTITY_ID'] = $entityId;
            $fields['FIELD_NAME'] = $fieldName;
        }

        $this->checkRequiredKeys(__METHOD__, $fields, array('ENTITY_ID', 'FIELD_NAME'));

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
     * @param $item
     * @param bool $transformEntityId
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Sprint\Migration\Exceptions\HelperException
     */
    protected function prepareExportUserTypeEntity($item, $transformEntityId = false) {
        if (empty($item)) {
            return $item;
        }

        if ($transformEntityId) {
            $item['ENTITY_ID'] = $this->transformEntityId(
                $item['ENTITY_ID']
            );
        }

        unset($item['ID']);
        return $item;
    }

    /**
     * @param $fieldId
     * @param bool $full
     * @return array
     */
    protected function getEnumValues($fieldId, $full = false) {
        $obEnum = new \CUserFieldEnum;
        $dbres = $obEnum->GetList(array(), array("USER_FIELD_ID" => $fieldId));

        $result = array();
        while ($enum = $dbres->Fetch()) {
            if ($full) {
                $result[] = $enum;
            } else {
                $result[] = array(
                    'VALUE' => $enum['VALUE'],
                    'DEF' => $enum['DEF'],
                    'SORT' => $enum['SORT'],
                    'XML_ID' => $enum['XML_ID'],
                );
            }
        }

        return $result;
    }

    /**
     * @param $enum
     * @param array $haystack
     * @return bool|mixed
     */
    protected function searchEnum($enum, $haystack = array()) {
        foreach ($haystack as $item) {
            if (!empty($item['XML_ID']) && $item['XML_ID'] == $enum['XML_ID']) {
                return $item;
            }
        }
        return false;
    }

}
