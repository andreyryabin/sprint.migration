<?php

namespace Sprint\Migration\Helpers;

use Sprint\Migration\Helper;

class UserTypeEntityHelper extends Helper
{

    private $transf = [];

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

    public function updateUserTypeEntityIfExists($entityId, $fieldName, $fields) {
        $item = $this->getUserTypeEntity($entityId, $fieldName);
        if (!$item) {
            return false;
        }

        return $this->updateUserTypeEntity($item['ID'], $fields);

    }

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

    public function exportUserTypeEntity($fieldId) {
        $item = $this->getUserTypeEntityById($fieldId);
        return $this->prepareExportUserTypeEntity($item, true);
    }

    public function exportUserTypeEntities($entityId = false) {
        $items = $this->getUserTypeEntities($entityId);
        $export = array();
        foreach ($items as $item) {
            $export[] = $this->prepareExportUserTypeEntity($item, true);
        }
        return $export;
    }

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

    public function getUserTypeEntity($entityId, $fieldName) {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $item = \CUserTypeEntity::GetList(array(), array(
            'ENTITY_ID' => $entityId,
            'FIELD_NAME' => $fieldName
        ))->Fetch();

        return (!empty($item)) ? $this->getUserTypeEntityById($item['ID']) : false;
    }

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

    public function transformEntityId($entityId) {
        if (0 === strpos($entityId, 'HLBLOCK_')) {
            $hlblockId = intval(substr($entityId, 8));
            $hlhelper = new HlblockHelper();
            $hlblock = $hlhelper->getHlblock($hlblockId);
            if (empty($hlblock)) {
                $this->throwException(__METHOD__, '%s not found', $entityId);
            }
            $entityId = 'HLBLOCK_' . $hlblock['NAME'];
        }
        return $entityId;
    }

    //version 2

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
            $ok = ($this->testMode) ? true : $this->addUserTypeEntity(
                $fields['ENTITY_ID'],
                $fields['FIELD_NAME'],
                $fields
            );

            $this->outNoticeIf($ok, 'Пользовательское поле %s: добавлено', $fields['FIELD_NAME']);
            return $ok;
        }

        unset($exportExists['MULTIPLE']);
        unset($fields['MULTIPLE']);

        if ($exportExists != $fields) {
            $ok = ($this->testMode) ? true : $this->updateUserTypeEntity($exists['ID'], $fields);
            $this->outNoticeIf($ok, 'Пользовательское поле %s: обновлено', $fields['FIELD_NAME']);
            return $ok;
        }

        $ok = ($this->testMode) ? true : $exists['ID'];
        $this->outIf($ok, 'Пользовательское поле %s: совпадает', $fields['FIELD_NAME']);

        return $ok;

    }
}
