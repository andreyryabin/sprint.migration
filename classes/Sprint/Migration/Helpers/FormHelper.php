<?php

namespace Sprint\Migration\Helpers;

use CDBResult;
use Sprint\Migration\Helper;

class FormHelper extends Helper
{
    /** @var \CDatabase $DB */
    private $db;

    public function __construct() {
        \CModule::IncludeModule('form');
        global $DB;
        $this->db = $DB;
    }

    public function getList($filter = array()) {
        $by = 's_name';
        $order = 'asc';
        $isFiltered = null;

        $dbres = \CForm::GetList($by, $order, $filter, $isFiltered);
        return $this->fetchAll($dbres);
    }

    /**
     * @param $formId
     * @param $what
     * @return array|null
     */
    public function getFormById($formId) {
        $formId = (int)$formId;

        $form = \CForm::GetByID($formId)->Fetch();
        if (empty($form)) {
            return false;
        }

        $form['arSITE'] = $this->exportSites($formId);

        $form["arMENU"] = $this->exportMenus($formId);

        $form['arGROUP'] = $this->exportRights($formId);

        $form['arMAIL_TEMPLATE'] = $this->exportMailTemplates($formId);

        return $form;
    }

    /**
     * @param string $sid
     * @return mixed
     */
    public function getFormIdBySid($sid) {
        $form = \CForm::GetBySID($sid)->Fetch();
        return ($form && isset($form['ID'])) ? $form['ID'] : 0;
    }

    /**
     * @param int $formId
     * @return array
     */
    public function exportRights($formId) {
        $userGroupHelper = new UserGroupHelper();

        $rights = [];
        $dbGroup = $this->db->Query("SELECT GROUP_ID, PERMISSION FROM b_form_2_group WHERE FORM_ID = {$formId}");
        while ($group = $dbGroup->Fetch()) {
            $groupCode = $userGroupHelper->getGroupCode($group['GROUP_ID']);
            if ($groupCode) {
                $rights[$groupCode] = $group["PERMISSION"];
            }
        }
        return $rights;
    }

    public function exportSites($formId) {
        return \CForm::GetSiteArray($formId);
    }

    /**
     * @param int $formId
     * @return array
     */
    public function exportMailTemplates($formId) {
        return \CForm::GetMailTemplateArray($formId);
    }

    public function exportMenus($formId) {
        $res = array();
        $dbres = \CForm::GetMenuList(array('FORM_ID' => $formId), 'N');
        while ($menuItem = $dbres->Fetch()) {
            $res[$menuItem["LID"]] = $menuItem["MENU"];
        }
        return $res;
    }


    /**
     * @param $form
     * @param $sid
     * @return bool|int
     * @throws \Exception
     */
    public function saveForm($form) {
        $this->checkRequiredKeys(__METHOD__, $form, array('SID'));

        $form['VARNAME'] = $form['SID'];

        $userGroupHelper = new UserGroupHelper();
        if (isset($form['arGROUP']) && is_array($form['arGROUP'])) {
            $arGroup = array();
            foreach ($form['arGROUP'] as $groupCode => $permissionValue) {
                $groupId = $userGroupHelper->getGroupId($groupCode);
                $arGroup[$groupId] = $permissionValue;
            }
            $form['arGROUP'] = $arGroup;
        }


        $formId = $this->getFormIdBySid($form['SID']);
        $formId = \CForm::Set($form, $formId, 'N');

        if ($formId) {
            return $formId;
        }

        $this->throwException(__METHOD__, $GLOBALS['strError']);
    }

    /**
     * @param $formId
     * @param $statuses
     * @throws \Exception
     */
    public function saveStatuses($formId, $statuses) {
        $currentStatuses = $this->getFormStatuses($formId);

        $updatedIds = array();

        foreach ($statuses as $status) {
            $status['FORM_ID'] = $formId;

            $statusId = false;
            foreach ($currentStatuses as $currentStatus) {
                if ($currentStatus['TITLE'] == $status['TITLE']) {
                    $statusId = $currentStatus['ID'];
                    $updatedIds[] = $currentStatus['ID'];
                    break;
                }
            }

            if (!\CFormStatus::Set($status, $statusId, 'N')) {
                $this->throwException(__METHOD__, $GLOBALS['strError']);
            }
        }

        foreach ($currentStatuses as $status) {
            if (!in_array($status['ID'], $updatedIds)) {
                \CFormStatus::Delete($status['ID'], 'N');
            }
        }

    }

    /**
     * @param $formId
     * @param $fields
     * @param $validators
     * @throws \Exception
     */
    public function saveFieldsWithValidators($formId, $fields, $validators) {
        $arValidators = [];
        foreach ($validators as $validator) {
            $arValidators[$validator['FIELD_ID']][] = $validator;
        }

        foreach ($fields as $field) {
            $answers = $field['_ANSWERS'];
            $validators = $arValidators[$field['ID']];
            $field['FORM_ID'] = $formId;

            unset($field['_ANSWERS']);
            unset($field['VARNAME']);
            unset($field['TIMESTAMP_X']);
            unset($field['ID']);
            $this->addField($formId, $field, $answers, $validators);
        }
    }

    /**
     * @param $formId
     * @param array $field
     * @param array $answers
     * @param array $validators
     * @throws \Exception
     */
    public function addField($formId, $field, $answers, $validators = array()) {
        $field['FORM_ID'] = $formId;
        $fieldId = \CFormField::Set($field);
        if (!$fieldId) {
            $this->throwException(__METHOD__, $GLOBALS['strError']);
        }

        if (!empty($validators)) {
            foreach ($answers as $answer) {
                $answer['QUESTION_ID'] = $fieldId;
                unset($answer['ID']);
                unset($answer['FIELD_ID']);
                unset($answer['TIMESTAMP_X']);
                $answerID = \CFormAnswer::Set($answer);
                if (!$answerID) {
                    $this->throwException(__METHOD__, $GLOBALS['strError']);
                }
            }
        }


        if (!empty($validators)) {
            foreach ($validators as $validator) {
                $validatorId = \CFormValidator::Set($formId, $fieldId, $validator['NAME']);
                if (!$validatorId) {
                    //  TODO - мигрировать валидаторы, ибо тут предполагается их наличие в системе
                    global $strError;
                }
            }
        }


    }

    /**
     * @return array
     */
    public function getFormStatuses($formId) {
        $dbStatuses = \CFormStatus::GetList($formId, $by = 's_sort', $order = 'asc', [], $f);
        return $this->fetchAll($dbStatuses);
    }

    /**
     * @return array
     */
    public function getFormFields($formId) {
        $dbFields = \CFormField::GetList($formId, 'ALL', $by = 's_sort', $order = 'asc', [], $f);
        $fields = $this->fetchAll($dbFields);
        foreach ($fields as $k => $field) {
            $fields[$k]['_ANSWERS'] = $this->getFieldAnswers($field['ID']);
        }
        return $fields;
    }

    /**
     * @param $fieldId
     * @return array
     */
    private function getFieldAnswers($fieldId) {
        $dbAnswers = \CFormAnswer::GetList($fieldId, $by = 's_sort', $order = 'asc', [], $f);
        return $this->fetchAll($dbAnswers);
    }

    /**
     * @return array
     */
    public function getFormValidators($formId) {
        $dbValidators = \CFormValidator::GetList($formId, [], $by = 's_sort', $order = 'asc');
        return $this->fetchAll($dbValidators);
    }


    /**
     * @param string $sid
     * @throws \Exception
     */
    public function deleteFormBySid($sid) {
        $id = $this->getFormIdBySid($sid);
        if (!$id) {
            return false;
        }

        if (\CForm::Delete($id)) {
            return true;
        }

        $this->throwException(__METHOD__, 'Cannot delete form "%s"', $sid);
    }


    /**
     * @param CDBResult $dbres
     * @return array
     */
    private function fetchAll(\CDBResult $dbres) {
        $res = array();

        while ($value = $dbres->Fetch()) {
            $res[] = $value;
        }

        return $res;
    }

}