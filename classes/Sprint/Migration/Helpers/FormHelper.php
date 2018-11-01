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
    public function getFormId($sid) {
        $form = \CForm::GetBySID($sid)->Fetch();
        return ($form && isset($form['ID'])) ? $form['ID'] : false;

    }

    public function getFormIdIfExists($sid) {
        $formId = $this->getFormId($sid);
        if ($formId) {
            return $formId;
        }

        $this->throwException(__METHOD__, "Form %s not found", $sid);

    }

    /**
     * @param int $formId
     * @return array
     */
    protected function exportRights($formId) {
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

    protected function exportSites($formId) {
        return \CForm::GetSiteArray($formId);
    }

    /**
     * @param int $formId
     * @return array
     */
    protected function exportMailTemplates($formId) {
        return \CForm::GetMailTemplateArray($formId);
    }

    protected function exportMenus($formId) {
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


        $formId = $this->getFormId($form['SID']);
        $formId = \CForm::Set($form, $formId, 'N');

        if ($formId) {
            return $formId;
        }

        $this->throwException(__METHOD__, $GLOBALS['strError']);
    }

    public function saveFields($formId, $fields) {

        $currentFields = $this->getFormFields($formId);
        $updatedIds = array();

        foreach ($fields as $field) {
            $field['FORM_ID'] = $formId;
            $field['VARNAME'] = $field['SID'];

            $answers = array();
            if (isset($field['ANSWERS'])) {
                if (is_array($field['ANSWERS'])) {
                    $answers = $field['ANSWERS'];
                }
                unset($field['ANSWERS']);
            }


            $validators = array();
            if (isset($field['VALIDATORS'])) {
                if (is_array($field['VALIDATORS'])) {
                    $validators = $field['VALIDATORS'];
                }
                unset($field['VALIDATORS']);
            }

            $fieldId = false;
            foreach ($currentFields as $currentField) {
                if (
                    !in_array($currentField['ID'], $updatedIds) &&
                    $currentField['SID'] == $field['SID']
                ) {
                    $fieldId = $currentField['ID'];
                    $updatedIds[] = $currentField['ID'];
                    break;
                }
            }

            $fieldId = \CFormField::Set($field, $fieldId, 'N');
            if (empty($fieldId)) {
                $this->throwException(__METHOD__, $GLOBALS['strError']);
            }

            $this->saveFieldAnswers($formId, $fieldId, $answers);
            $this->saveFieldValidators($formId, $fieldId, $validators);

        }

        foreach ($currentFields as $currentField) {
            if (!in_array($currentField['ID'], $updatedIds)) {
                \CFormField::Delete($currentField['ID'], 'N');
            }
        }
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
                if (
                    !in_array($currentStatus['ID'], $updatedIds) &&
                    $currentStatus['TITLE'] == $status['TITLE']
                ) {
                    $statusId = $currentStatus['ID'];
                    $updatedIds[] = $currentStatus['ID'];
                    break;
                }
            }

            $statusId = \CFormStatus::Set($status, $statusId, 'N');
            if (empty($statusId)) {
                $this->throwException(__METHOD__, $GLOBALS['strError']);
            }
        }

        foreach ($currentStatuses as $currentStatus) {
            if (!in_array($currentStatus['ID'], $updatedIds)) {
                \CFormStatus::Delete($currentStatus['ID'], 'N');
            }
        }

    }


    protected function saveFieldAnswers($formId, $fieldId, $answers) {
        $currentAnswers = $this->getFieldAnswers($fieldId);

        $updatedIds = array();

        foreach ($answers as $index => $answer) {
            $answerId = false;

            foreach ($currentAnswers as $currentAnswer) {
                if (
                    !in_array($currentAnswer['ID'], $updatedIds) &&
                    $currentAnswer['MESSAGE'] == $answer['MESSAGE']
                ) {
                    $answerId = $currentAnswer['ID'];
                    $updatedIds[] = $currentAnswer['ID'];
                    break;
                }
            }

            $answer['FIELD_ID'] = $fieldId;

            $answerId = \CFormAnswer::Set(
                $answer,
                $answerId
            );
            if (empty($answerId)) {
                $this->throwException(__METHOD__, $GLOBALS['strError']);
            }
        }


        foreach ($currentAnswers as $currentAnswer) {
            if (!in_array($currentAnswer['ID'], $updatedIds)) {
                \CFormAnswer::Delete($currentAnswer['ID'], $fieldId);
            }
        }
    }


    protected function saveFieldValidators($formId, $fieldId, $validators) {

        \CFormValidator::Clear($fieldId);

        foreach ($validators as $index => $validator) {

            $validatorId = \CFormValidator::Set(
                $formId,
                $fieldId,
                $validator['NAME'],
                $validator['PARAMS'],
                $validator['C_SORT']
            );

            if (empty($validatorId)) {
                $this->throwException(__METHOD__, $GLOBALS['strError']);
            }
        }

    }


    /**
     * @return array
     */
    public function getFormStatuses($formId) {
        $dbres = \CFormStatus::GetList($formId, $by = 's_sort', $order = 'asc', [], $f);
        return $this->fetchAll($dbres);
    }

    /**
     * @return array
     */
    public function getFormFields($formId) {
        $dbres = \CFormField::GetList($formId, 'ALL', $by = 's_sort', $order = 'asc', [], $f);
        $fields = $this->fetchAll($dbres);
        foreach ($fields as $index => $field) {
            $fields[$index]['ANSWERS'] = $this->getFieldAnswers($field['ID']);
            $fields[$index]['VALIDATORS'] = $this->getFieldValidators($field['ID']);
        }
        return $fields;
    }

    /**
     * @param $fieldId
     * @return array
     */
    protected function getFieldAnswers($fieldId) {
        $dbres = \CFormAnswer::GetList($fieldId, $by = 's_sort', $order = 'asc', [], $f);
        return $this->fetchAll($dbres);
    }

    /**
     * @param $fieldId
     * @return array
     */
    protected function getFieldValidators($fieldId) {
        $dbres = \CFormValidator::GetList($fieldId, array(), $by = 's_sort', $order = 'asc');
        return $this->fetchAll($dbres);
    }


    /**
     * @param string $sid
     * @throws \Exception
     */
    public function deleteFormIfExists($sid) {
        $formId = $this->getFormId($sid);

        if (!$formId) {
            return false;
        }

        if (\CForm::Delete($formId)) {
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