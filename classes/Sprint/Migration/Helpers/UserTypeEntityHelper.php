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
        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if ($item) {
            return $item['ID'];
        }

        return $this->addUserTypeEntity($entityId, $fieldName, $fields);
    }

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

    public function updateUserTypeEntity($id, $fields) {
        $enums = array();
        if (isset($fields['ENUM_VALUES'])) {
            $enums = $fields['ENUM_VALUES'];
            unset($fields['ENUM_VALUES']);
        }

        $entity = new \CUserTypeEntity;
        $userFieldUpdated = $entity->Update($id, $fields);

        $enumsCreated = true;
        if ($userFieldUpdated && $fields['USER_TYPE_ID'] == 'enumeration') {
            $enumsCreated = $this->setUserTypeEntityEnumValues($id, $enums);
        }

        if ($userFieldUpdated && $enumsCreated) {
            return $id;
        }
        /* @global $APPLICATION \CMain */
        global $APPLICATION;
        if ($APPLICATION->GetException()) {
            $this->throwException(__METHOD__, $APPLICATION->GetException()->GetString());
        } else {
            $this->throwException(__METHOD__, 'UserType %s not updated', $id);
        }
    }

    public function updateUserTypeEntityIfExists($entityId, $fieldName, $fields) {
        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if (!$item) {
            return false;
        }

        return $this->updateUserTypeEntity($item['ID'], $fields);

    }

    public function getUserTypeEntities($entityId = false) {
        $filter = array();

        if ($entityId) {
            $filter = is_array($entityId) ? $entityId : array(
                'ENTITY_ID' => $entityId
            );
        }

        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = \CUserTypeEntity::GetList(array(), $filter);
        $result = array();
        while ($item = $dbres->Fetch()) {
            $result[] = $this->getUserTypeEntityById($item['ID']);
        }
        return $result;
    }

    public function exportUserTypeEntities() {
        $items = $this->getUserTypeEntities();

        $exportItems = array();
        foreach ($items as $item) {
            $exportItems[] = $this->prepareExportUserTypeEntity($item);
        }

        return $exportItems;
    }

    public function exportUserTypeEntity($entityId, $fieldName) {
        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if (empty($item)) {
            return false;
        }

        return $this->prepareExportUserTypeEntity($item);
    }

    public function prepareExportUserTypeEntity($item) {
        unset($item['ID']);

        return $item;
    }

    public function getUserTypeEntity($entityId, $fieldName) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $item = \CUserTypeEntity::GetList(array(), array(
            'ENTITY_ID' => $entityId,
            'FIELD_NAME' => $fieldName
        ))->Fetch();

        return (!empty($item)) ? $this->getUserTypeEntityById($item['ID']) : false;
    }

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

    public function deleteUserTypeEntityIfExists($entityId, $fieldName) {
        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if (!$item) {
            return false;
        }

        $entity = new \CUserTypeEntity();
        if ($entity->Delete($item['ID'])) {
            return true;
        }

        $this->throwException(__METHOD__, 'UserType not deleted');
    }

    public function deleteUserTypeEntity($entityId, $fieldName) {
        return $this->deleteUserTypeEntityIfExists($entityId, $fieldName);
    }

    public function getUserTypeEntityById($fieldId) {
        $item = \CUserTypeEntity::GetByID($fieldId);

        if ($item && $item['USER_TYPE_ID'] == 'enumeration') {
            $item['ENUM_VALUES'] = $this->getEnumValues($fieldId, false);
        }

        return $item;
    }

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

    protected function searchEnum($enum, $haystack = array()) {
        foreach ($haystack as $item) {
            if (!empty($item['XML_ID']) && $item['XML_ID'] == $enum['XML_ID']) {
                return $item;
            }
        }
        return false;
    }


    //version 2

    public function saveUserTypeEntity($entityId, $fieldName, $fields) {
        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if ($item) {
            return $this->updateUserTypeEntity($item['ID'], $fields);
        } else {
            return $this->addUserTypeEntity($entityId, $fieldName, $fields);
        }

    }
}
