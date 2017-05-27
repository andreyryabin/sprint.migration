<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class UserTypeEntityHelper extends Helper
{
    public function addUserTypeEntitiesIfNotExists($entityId, array $fields) {
        foreach ($fields as $field) {
            $this->addUserTypeEntityIfNotExists($entityId, $field["FIELD_NAME"], $field);
        }
    }

    public function deleteUserTypeEntitiesIfExists($entityId, array $fields) {
        foreach ($fields as $fieldName) {
            $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
        }
    }

    public function addUserTypeEntityIfNotExists($entityId, $fieldName, $fields) {
        /* @global $APPLICATION \CMain */
        global $APPLICATION;

        $aItem = $this->getUserTypeEntity($entityId, $fieldName);
        if ($aItem){
            return $aItem['ID'];
        }

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

        $obUserField = new \CUserTypeEntity;
        $userFieldId = $obUserField->Add($fields);

        if ($userFieldId) {
            return $userFieldId;
        }

        if ($APPLICATION->GetException()) {
            $this->throwException(__METHOD__, $APPLICATION->GetException()->GetString());
        } else {
            $this->throwException(__METHOD__, 'UserType %s not added', $fieldName);
        }
    }

    public function getUserTypeEntities($entityId) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CUserTypeEntity::GetList(array(), array('ENTITY_ID' => $entityId));
        $result = array();
        while ($aItem = $dbRes->Fetch()){
            $result[] = \CUserTypeEntity::GetByID($aItem['ID']);
        }
        return $result;
    }

    public function getUserTypeEntity($entityId, $fieldName) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CUserTypeEntity::GetList(array(), array('ENTITY_ID' => $entityId, 'FIELD_NAME' => $fieldName));
        $aItem = $dbRes->Fetch();
        return (!empty($aItem)) ? \CUserTypeEntity::GetByID($aItem['ID']) : false;
    }

    public function deleteUserTypeEntityIfExists($entityId, $fieldName) {
        $aItem = $this->getUserTypeEntity($entityId, $fieldName);
        if (!$aItem){
            return false;
        }

        $entity = new \CUserTypeEntity();
        if ($entity->Delete($aItem['ID'])) {
            return true;
        }

        $this->throwException(__METHOD__, 'UserType not deleted');
    }

    /* @deprecated */
    public function deleteUserTypeEntity($entityId, $fieldName) {
        return $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
    }


}
