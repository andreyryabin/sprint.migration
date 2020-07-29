<?php

namespace Sprint\Migration\Helpers;

use Bitrix\Main\Application;
use Bitrix\Main\Db\SqlQueryException;
use CForm;
use CFormAnswer;
use CFormField;
use CFormStatus;
use CFormValidator;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;

class FormHelper extends Helper
{
    /**
     * FormHelper constructor.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->checkModules(['form']);
    }

    /**
     * @param array $filter
     *
     * @return array
     */
    public function getList($filter = [])
    {
        $by = 's_name';
        $order = 'asc';
        $isFiltered = null;

        $dbres = CForm::GetList($by, $order, $filter, $isFiltered);
        return $this->fetchAll($dbres);
    }

    /**
     * @param $formId
     *
     * @throws HelperException
     * @throws SqlQueryException
     * @return array|bool
     */
    public function getFormById($formId)
    {
        $formId = (int)$formId;

        $form = CForm::GetByID($formId)->Fetch();
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
     * @param $sid
     *
     * @return bool|int
     */
    public function getFormId($sid)
    {
        $form = CForm::GetBySID($sid)->Fetch();
        return ($form && isset($form['ID'])) ? $form['ID'] : false;
    }

    /**
     * @param $sid
     *
     * @throws HelperException
     * @return int|void
     */
    public function getFormIdIfExists($sid)
    {
        $formId = $this->getFormId($sid);
        if ($formId) {
            return $formId;
        }

        $this->throwException(
            __METHOD__,
            Locale::getMessage('ERR_FORM_NOT_FOUND', ['#NAME#' => $sid])
        );
    }

    /**
     * @param $form
     *
     * @throws HelperException
     * @return int|void
     */
    public function saveForm($form)
    {
        $this->checkRequiredKeys(__METHOD__, $form, ['SID']);

        $form['VARNAME'] = $form['SID'];

        $userGroupHelper = new UserGroupHelper();
        if (isset($form['arGROUP']) && is_array($form['arGROUP'])) {
            $arGroup = [];
            foreach ($form['arGROUP'] as $groupCode => $permissionValue) {
                $groupId = $userGroupHelper->getGroupId($groupCode);
                $arGroup[$groupId] = $permissionValue;
            }
            $form['arGROUP'] = $arGroup;
        }

        $formId = $this->getFormId($form['SID']);

        $formId = CForm::Set($form, $formId, 'N');

        if ($formId) {
            return $formId;
        }

        $this->throwException(
            __METHOD__,
            $GLOBALS['strError']
        );
    }

    /**
     * @param $formId
     * @param $fields
     *
     * @throws HelperException
     */
    public function saveFields($formId, $fields)
    {
        $currentFields = $this->getFormFields($formId);
        $updatedIds = [];

        foreach ($fields as $field) {
            $field['FORM_ID'] = $formId;
            $field['VARNAME'] = $field['SID'];

            $answers = [];
            if (isset($field['ANSWERS'])) {
                if (is_array($field['ANSWERS'])) {
                    $answers = $field['ANSWERS'];
                }
                unset($field['ANSWERS']);
            }

            $validators = [];
            if (isset($field['VALIDATORS'])) {
                if (is_array($field['VALIDATORS'])) {
                    $validators = $field['VALIDATORS'];
                }
                unset($field['VALIDATORS']);
            }

            $fieldId = false;
            foreach ($currentFields as $currentField) {
                if (
                    !in_array($currentField['ID'], $updatedIds)
                    && $currentField['SID'] == $field['SID']
                ) {
                    $fieldId = $currentField['ID'];
                    $updatedIds[] = $currentField['ID'];
                    break;
                }
            }
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $fieldId = CFormField::Set($field, $fieldId, 'N');
            if (empty($fieldId)) {
                $this->throwException(
                    __METHOD__,
                    $GLOBALS['strError']
                );
            }

            $this->saveFieldAnswers($fieldId, $answers);
            $this->saveFieldValidators($formId, $fieldId, $validators);
        }

        foreach ($currentFields as $currentField) {
            if (!in_array($currentField['ID'], $updatedIds)) {
                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                CFormField::Delete($currentField['ID'], 'N');
            }
        }
    }

    /**
     * @param $formId
     * @param $statuses
     *
     * @throws Exception
     */
    public function saveStatuses($formId, $statuses)
    {
        $currentStatuses = $this->getFormStatuses($formId);

        $updatedIds = [];

        foreach ($statuses as $status) {
            $status['FORM_ID'] = $formId;

            $statusId = false;
            foreach ($currentStatuses as $currentStatus) {
                if (
                    !in_array($currentStatus['ID'], $updatedIds)
                    && $currentStatus['TITLE'] == $status['TITLE']
                ) {
                    $statusId = $currentStatus['ID'];
                    $updatedIds[] = $currentStatus['ID'];
                    break;
                }
            }

            //Зададим доступы к статусу для создателя результата
            //Сделано по аналогии с тем, как  у самого Битрикс при создании новой веб-формы в упрощенном режиме
            //см. \bitrix\modules\form\admin\form_edit.php#295
            $status['arPERMISSION_VIEW'] = $status['arPERMISSION_VIEW'] ? $status['arPERMISSION_VIEW'] : [0];
            $status['arPERMISSION_MOVE'] = $status['arPERMISSION_MOVE'] ? $status['arPERMISSION_MOVE'] : [0];
            $status['arPERMISSION_EDIT'] = $status['arPERMISSION_EDIT'] ? $status['arPERMISSION_EDIT'] : [0];
            $status['arPERMISSION_DELETE'] = $status['arPERMISSION_DELETE'] ? $status['arPERMISSION_DELETE'] : [0];

            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $statusId = CFormStatus::Set($status, $statusId, 'N');
            if (empty($statusId)) {
                $this->throwException(
                    __METHOD__,
                    $GLOBALS['strError']
                );
            }
        }

        foreach ($currentStatuses as $currentStatus) {
            if (!in_array($currentStatus['ID'], $updatedIds)) {
                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                CFormStatus::Delete($currentStatus['ID'], 'N');
            }
        }
    }

    /**
     * @param $formId
     *
     * @return array
     */
    public function getFormStatuses($formId)
    {
        $isFiltered = false;
        $by = 's_sort';
        $order = 'asc';
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = CFormStatus::GetList($formId, $by, $order, [], $isFiltered);
        return $this->fetchAll($dbres);
    }

    /**
     * @param $formId
     *
     * @return array
     */
    public function getFormFields($formId)
    {
        $isFiltered = false;
        $by = 's_sort';
        $order = 'asc';
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = CFormField::GetList($formId, 'ALL', $by, $order, [], $isFiltered);
        $fields = $this->fetchAll($dbres);
        foreach ($fields as $index => $field) {
            $fields[$index]['ANSWERS'] = $this->getFieldAnswers($field['ID']);
            $fields[$index]['VALIDATORS'] = $this->getFieldValidators($field['ID']);
        }
        return $fields;
    }

    /**
     * @param $sid
     *
     * @throws HelperException
     * @return bool|void
     */
    public function deleteFormIfExists($sid)
    {
        $formId = $this->getFormId($sid);

        if (!$formId) {
            return false;
        }
        if (CForm::Delete($formId)) {
            return true;
        }

        $this->throwException(
            __METHOD__,
            Locale::getMessage(
                'ERR_CANT_DELETE_FORM', [
                    '#NAME#' => $sid,
                ]
            )
        );
    }

    /**
     * @param $fieldId
     *
     * @return array
     */
    protected function getFieldAnswers($fieldId)
    {
        $isFiltered = false;
        $by = 's_sort';
        $order = 'asc';
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = CFormAnswer::GetList($fieldId, $by, $order, [], $isFiltered);
        return $this->fetchAll($dbres);
    }

    /**
     * @param $fieldId
     *
     * @return array
     */
    protected function getFieldValidators($fieldId)
    {
        $by = 's_sort';
        $order = 'asc';
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        $dbres = CFormValidator::GetList($fieldId, [], $by, $order);
        return $this->fetchAll($dbres);
    }

    /**
     * @param int $formId
     *
     * @throws HelperException
     * @throws SqlQueryException
     * @return array
     */
    protected function exportRights($formId)
    {
        $userGroupHelper = new UserGroupHelper();

        $dbres = Application::getConnection()->query(
            "SELECT GROUP_ID, PERMISSION FROM b_form_2_group WHERE FORM_ID = {$formId}"
        );

        $rights = [];
        while ($group = $dbres->fetch()) {
            $groupCode = $userGroupHelper->getGroupCode($group['GROUP_ID']);
            if ($groupCode) {
                $rights[$groupCode] = $group["PERMISSION"];
            }
        }
        return $rights;
    }

    /**
     * @param $formId
     *
     * @return array|bool
     */
    protected function exportSites($formId)
    {
        return CForm::GetSiteArray($formId);
    }

    /**
     * @param int $formId
     *
     * @return array
     */
    protected function exportMailTemplates($formId)
    {
        return CForm::GetMailTemplateArray($formId);
    }

    /**
     * @param $formId
     *
     * @return array
     */
    protected function exportMenus($formId)
    {
        $res = [];
        $dbres = CForm::GetMenuList(['FORM_ID' => $formId], 'N');
        while ($menuItem = $dbres->Fetch()) {
            $res[$menuItem["LID"]] = $menuItem["MENU"];
        }
        return $res;
    }

    /**
     * @param $fieldId
     * @param $answers
     *
     * @throws HelperException
     */
    protected function saveFieldAnswers($fieldId, $answers)
    {
        $currentAnswers = $this->getFieldAnswers($fieldId);

        $updatedIds = [];

        foreach ($answers as $index => $answer) {
            $answerId = false;

            foreach ($currentAnswers as $currentAnswer) {
                if (
                    !in_array($currentAnswer['ID'], $updatedIds)
                    && $currentAnswer['MESSAGE'] == $answer['MESSAGE']
                ) {
                    $answerId = $currentAnswer['ID'];
                    $updatedIds[] = $currentAnswer['ID'];
                    break;
                }
            }

            $answer['FIELD_ID'] = $fieldId;

            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $answerId = CFormAnswer::Set(
                $answer,
                $answerId
            );
            if (empty($answerId)) {
                $this->throwException(
                    __METHOD__,
                    $GLOBALS['strError']
                );
            }
        }

        foreach ($currentAnswers as $currentAnswer) {
            if (!in_array($currentAnswer['ID'], $updatedIds)) {
                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                CFormAnswer::Delete($currentAnswer['ID'], $fieldId);
            }
        }
    }

    /**
     * @param $formId
     * @param $fieldId
     * @param $validators
     *
     * @throws HelperException
     */
    protected function saveFieldValidators($formId, $fieldId, $validators)
    {
        /** @noinspection PhpDynamicAsStaticMethodCallInspection */
        CFormValidator::Clear($fieldId);

        foreach ($validators as $index => $validator) {
            /** @noinspection PhpDynamicAsStaticMethodCallInspection */
            $validatorId = CFormValidator::Set(
                $formId,
                $fieldId,
                $validator['NAME'],
                $validator['PARAMS'],
                $validator['C_SORT']
            );

            if (empty($validatorId)) {
                $this->throwException(
                    __METHOD__,
                    $GLOBALS['strError']
                );
            }
        }
    }
}
