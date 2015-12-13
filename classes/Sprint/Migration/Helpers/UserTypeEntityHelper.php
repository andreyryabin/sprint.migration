<?php

namespace Sprint\Migration\Helpers;
use Sprint\Migration\Helper;

class UserTypeEntityHelper extends Helper
{


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

        $fields = array_merge($default, $fields);
        $fields['FIELD_NAME'] = $fieldName;
        $fields['ENTITY_ID'] = $entityId;

        $obUserField = new \CUserTypeEntity;
        $id = $obUserField->Add($fields);

        if ($id) {
            return $id;
        }

        if ($APPLICATION->GetException()) {
            $this->throwException(__METHOD__, $APPLICATION->GetException()->GetString());
        } else {
            $this->throwException(__METHOD__, 'UserType %s not added', $fieldName);
        }
    }


    public function getUserTypeEntity($entityId, $fieldName) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbRes = \CUserTypeEntity::GetList(array(), array('ENTITY_ID' => $entityId, 'FIELD_NAME' => $fieldName));
        $aItem = $dbRes->Fetch();
        return (!empty($aItem)) ? $aItem : false;
    }

    public function deleteUserTypeEntityIfExists($entityId, $fieldName) {
        $aItem = $this->getUserTypeEntity($entityId, $fieldName);
        if ($aItem){
            return false;
        }

        $oEntity = new \CUserTypeEntity();
        if ($oEntity->Delete($aItem['ID'])) {
            return true;
        }

        $this->throwException(__METHOD__, 'UserType not deleted');
    }

    public function deleteUserTypeEntity($entityId, $fieldName) {
        return $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
    }


}