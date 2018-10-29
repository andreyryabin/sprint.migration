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
        if (!$form) {
            return null;
        }

        $dbSites = $this->db->Query("SELECT SITE_ID FROM b_form_2_site WHERE FORM_ID = {$formId}");
        $form['arSITE'] = [];
        while ($ar = $dbSites->Fetch()) {
            $form['arSITE'][] = $ar["SITE_ID"];
        }
        $dbMenu = $this->db->Query("SELECT LID, MENU FROM b_form_menu WHERE FORM_ID = {$formId}");
        $form['arMENU'] = [];
        while ($menu = $dbMenu->Fetch()) {
            $form['arMENU'][$menu['LID']] = $menu["MENU"];
        }

        return $form;
    }


    /**
     * @param int $formId
     * @return array
     */
    public function getRights($formId) {
        $rights = [];
        $dbGroup = $this->db->Query("SELECT GROUP_ID, PERMISSION FROM b_form_2_group WHERE FORM_ID = {$formId}");
        while ($group = $dbGroup->Fetch()) {
            $rights[$group['GROUP_ID']] = $group["PERMISSION"];
        }
        return $rights;
    }


    /**
     * @param int $formId
     * @return array
     */
    public function getMailTemplates($formId) {
        $templates = [];
        $dbTemplate = $this->db->Query("SELECT MAIL_TEMPLATE_ID FROM b_form_2_mail_template WHERE FORM_ID = {$formId}");
        while ($tmpl = $dbTemplate->Fetch()) {
            $templates[] = $tmpl["MAIL_TEMPLATE_ID"];
        }
        return $templates;
    }

    /**
     * @param $form
     * @param $sid
     * @return bool|int
     * @throws \Exception
     */
    public function saveForm($form, $sid) {
        $this->db->StartTransaction();
        $formArray = $form['FORM'];
        $oldSid = $formArray['SID'];
        $formArray['SID'] = $sid;
        $formArray['VARNAME'] = $sid;
        $formArray['MAIL_EVENT_TYPE'] = str_replace($oldSid, $sid, $formArray['MAIL_EVENT_TYPE']);
        $formId = \CForm::Set($formArray);
        if (!$formId) {
            $this->throwException(__METHOD__, $GLOBALS['strError']);
        }
        try {
            $this->saveStatuses($formId, $form['STATUSES']);
            $this->saveFieldsWithValidators($formId, $form['FIELDS'], $form['VALIDATORS']);
        } catch (\Exception $e) {
            $this->db->Rollback();
            throw $e;
        }
        $addNewTemplate = isset($formArray['arMAIL_TEMPLATE']) ? 'N' : 'Y';
        \CForm::SetMailTemplate($formId, $addNewTemplate);
        $this->db->Commit();

        return $formId;
    }

    /**
     * @param $formId
     * @param $statuses
     * @throws \Exception
     */
    public function saveStatuses($formId, $statuses) {
        foreach ($statuses as $status) {
            $status['FORM_ID'] = $formId;
            unset($status['TIMESTAMP_X']);
            unset($status['RESULTS']);
            unset($status['ID']);
            $statusID = \CFormStatus::Set($status);
            if (!$statusID) {
                $this->throwException(__METHOD__, $GLOBALS['strError']);
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

        foreach ($validators as $validator) {
            $validatorId = \CFormValidator::Set($formId, $fieldId, $validator['NAME']);
            if (!$validatorId) {
                //  TODO - мигрировать валидаторы, ибо тут предполагается их наличие в системе
                global $strError;
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
     * @param CDBResult $dbres
     * @return array
     */
    private function fetchAll(\CDBResult $dbres) {
        $res = [];
        while ($value = $dbres->Fetch()) {
            $res[] = $value;
        }
        return $res;
    }

    /**
     * @param string $sid
     * @throws \Exception
     */
    public function deleteFormBySid($sid) {
        $id = $this->getFormIdBySid($sid);
        $res = \CForm::Delete($id);
        if (!$res) {
            $this->throwException(__METHOD__,'Cannot delete form "%s"', $sid);
        }
    }

    /**
     * @param string $sid
     * @return mixed
     */
    public function getFormIdBySid($sid) {
        $form = \CForm::GetBySID($sid)->Fetch();
        return ($form && isset($form['ID'])) ? $form['ID'] : false;
    }

}