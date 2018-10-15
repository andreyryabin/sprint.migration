<?php

namespace Sprint\Migration\Helpers;

use CDBResult;
use Sprint\Migration\Helper;

class FormHelper extends Helper
{
    private $formId;

    public function initForm($formId)
    {
        $form = \CForm::GetByID($formId)->Fetch();
        if(!$form){
            return null;
        }
        $this->formId = $formId;
        return $form;
    }

    public function getFormStatuses()
    {
        $dbStatuses = \CFormStatus::GetList($this->formId, $by = 's_sort', $order = 'asc', [],$f);
        return $this->fetchDbRes($dbStatuses);
    }

    public function getFormFields()
    {
        $dbFields = \CFormField::GetList($this->formId, 'ALL', $by='s_sort', $order='asc', [], $f);
        $fields = $this->fetchDbRes($dbFields);
        foreach($fields as $k => $field){
            $fields[$k]['_ANSWERS'] = $this->getFieldAnswers($field['ID']);
        }
        return $fields;
    }

    private function getFieldAnswers($field_id)
    {
        $dbAnswers = \CFormAnswer::GetList($field_id, $by='s_sort', $order='asc', [], $f);
        return $this->fetchDbRes($dbAnswers);
    }

    public function getFormValidators()
    {
        $dbValidators = \CFormValidator::GetList($this->formId, [], $by = 's_sort', $order = 'asc');
        return $this->fetchDbRes($dbValidators);
    }


    private function fetchDbRes(CDBResult $dbRes){
        $res = [];
        while($value = $dbRes->Fetch()){
            $res[] = $value;
        }
        return $res;
    }

}