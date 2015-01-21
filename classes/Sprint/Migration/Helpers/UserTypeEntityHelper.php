<?php

namespace Sprint\Migration\Helpers;
/**
 * USER_TYPE_ID
 * video Видео
 * string Строка
 * integer Целое число
 * double Число
 * datetime
 * Дата со временем
 * date Дата
 * boolean Да/Нет
 * file Файл
 * enumeration Список
 * iblock_section Привязка к разделам инф. блоков
 * iblock_element Привязка к элементам инф. блоков
 * vote Опрос
 * string_formatted Шаблон
 * SHOW_FILTER    N не показывать    I точное совпадение    E поиск по маске    S поиск по подстроке
 */

class UserTypeEntityHelper
{


    public function addUserTypeEntityIfNotExists($entityId, $fieldName, $fields) {
        $id = 0;
        if (!$this->getUserTypeEntity($entityId, $fieldName)) {
            $id = $this->addUserTypeEntity($entityId, $fieldName, $fields);
        }

        return $id;
    }


    public function getUserTypeEntity($entityId, $fieldName) {
        $dbRes = \CUserTypeEntity::GetList(array(), array('ENTITY_ID' => $entityId, 'FIELD_NAME' => $fieldName));
        $aItem = $dbRes->Fetch();
        return (!empty($aItem)) ? $aItem : false;
    }

    public function deleteUserTypeEntity($entityId, $fieldName) {
        $aItem = $this->getUserTypeEntity($entityId, $fieldName);

        if ($aItem) {
            $oEntity = new \CUserTypeEntity();
            return $oEntity->Delete($aItem['ID']);
        }

        return false;
    }

    protected function addUserTypeEntity($entityId, $fieldName, $fields) {
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

        $fields = array_merge($default, $fields);
        $fields['FIELD_NAME'] = $fieldName;
        $fields['ENTITY_ID'] = $entityId;

        $obUserField = new \CUserTypeEntity;
        $id = $obUserField->Add($fields);

        return $id;
    }


}