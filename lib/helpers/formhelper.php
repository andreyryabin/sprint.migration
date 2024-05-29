<?php

namespace Sprint\Migration\Helpers;

use CForm;
use CFormAnswer;
use CFormField;
use CFormStatus;
use CFormValidator;
use Exception;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Helper;
use Sprint\Migration\Locale;
use Sprint\Migration\Tables\FormGroupTable;

class FormHelper extends Helper
{
    /**
     * FormHelper constructor.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->checkModules(['form']);
    }

    public function getList(array $filter = []): array
    {
        $by = 's_sid';
        $order = 'asc';
        $isFiltered = null;

        $dbres = CForm::GetList($by, $order, $filter, $isFiltered);
        return $this->fetchAll($dbres);
    }

    /**
     * @throws HelperException
     */
    public function exportFormById(int $formId): array
    {
        return $this->unsetKeys([
            'ID',
            'TIMESTAMP_X',
            'VARNAME',
        ], $this->getFormById($formId));
    }

    /**
     * @throws HelperException
     */
    public function getFormById(int $formId): array
    {
        $form = CForm::GetByID($formId)->Fetch();
        if (empty($form)) {
            throw new HelperException('Form "%s" not found', $formId);
        }

        $form['arSITE'] = $this->exportSites($formId);

        $form["arMENU"] = $this->exportMenus($formId);

        $form['arGROUP'] = $this->exportRights($formId);

        $form['arMAIL_TEMPLATE'] = $this->exportMailTemplates($formId);

        return $form;
    }

    public function getFormId(string $formSid): int
    {
        $form = CForm::GetBySID($formSid)->Fetch();
        return ($form && isset($form['ID'])) ? $form['ID'] : 0;
    }

    /**
     * @param $sid
     *
     * @throws HelperException
     * @return int
     */
    public function getFormIdIfExists($sid): int
    {
        $formId = $this->getFormId($sid);
        if ($formId) {
            return $formId;
        }

        throw new HelperException(Locale::getMessage('ERR_FORM_NOT_FOUND', ['#NAME#' => $sid]));
    }

    /**
     * @throws HelperException
     */
    public function saveForm(array $form): int
    {
        $this->checkRequiredKeys($form, ['SID']);

        $form['VARNAME'] = $form['SID'];

        $userGroupHelper = new UserGroupHelper();
        if (isset($form['arGROUP']) && is_array($form['arGROUP'])) {
            $arGroup = [];
            foreach ($form['arGROUP'] as $groupCode => $permissionValue) {
                $groupId = $userGroupHelper->getGroupId($groupCode);
                if ($groupId) {
                    $arGroup[$groupId] = $permissionValue;
                }
            }
            $form['arGROUP'] = $arGroup;
        }

        $eventHelper = new EventHelper();
        if (isset($form['arMAIL_TEMPLATE']) && is_array($form['arMAIL_TEMPLATE'])) {
            $arTemplates = [];
            foreach ($form['arMAIL_TEMPLATE'] as $templateId) {
                $templateId = $eventHelper->getEventMessageIdByUidFilter($templateId);
                if ($templateId) {
                    $arTemplates[] = $templateId;
                }
            }
            $form['arMAIL_TEMPLATE'] = $arTemplates;
        }

        $formId = $this->getFormId($form['SID']);

        $formId = CForm::Set($form, $formId, 'N');

        if ($formId) {
            return $formId;
        }

        throw new HelperException($GLOBALS['strError']);
    }

    /**
     * @throws HelperException
     */
    public function saveField(int $formId, array $field): int
    {
        $this->checkRequiredKeys($field, ['SID']);

        $fieldId = $this->getFormFieldIdBySid($formId, $field['SID']);

        return $this->replaceField($formId, $fieldId, $field);
    }

    public function getFormFieldIdBySid(int $formId, string $fieldSid)
    {
        $exists = CFormField::GetBySID($fieldSid, $formId)->Fetch();
        return ($exists) ? $exists['ID'] : 0;
    }

    /**
     * @throws HelperException
     */
    public function saveFields(int $formId, array $fields): array
    {
        return $this->updateFields($formId, $fields, true);
    }

    /**
     * @throws HelperException
     */
    public function updateFields(int $formId, array $fields, bool $deleteOldFields = false): array
    {
        $currentIdsBySid = [];
        foreach ($this->getFormFields($formId) as $currentField) {
            $currentIdsBySid[$currentField['SID']] = $currentField['ID'];
        }

        $updatedIds = [];

        foreach ($fields as $field) {
            $this->checkRequiredKeys($field, ['SID']);

            $fieldId = $currentIdsBySid[$field['SID']] ?? 0;

            $updatedIds[] = $this->replaceField($formId, $fieldId, $field);
        }

        if ($deleteOldFields) {
            foreach ($currentIdsBySid as $currentId) {
                if (!in_array($currentId, $updatedIds)) {
                    $this->deleteFormField($currentId);
                }
            }
        }

        return $updatedIds;
    }

    /**
     * @throws HelperException
     */
    public function saveStatuses(int $formId, array $statuses): array
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
            $status['arPERMISSION_VIEW'] = $status['arPERMISSION_VIEW'] ?: [0];
            $status['arPERMISSION_MOVE'] = $status['arPERMISSION_MOVE'] ?: [0];
            $status['arPERMISSION_EDIT'] = $status['arPERMISSION_EDIT'] ?: [0];
            $status['arPERMISSION_DELETE'] = $status['arPERMISSION_DELETE'] ?: [0];

            $statusId = CFormStatus::Set($status, $statusId, 'N');
            if (empty($statusId)) {
                throw new HelperException($GLOBALS['strError']);
            }
        }

        foreach ($currentStatuses as $currentStatus) {
            if (!in_array($currentStatus['ID'], $updatedIds)) {
                $this->deleteFormStatus($currentStatus['ID']);
            }
        }

        return $updatedIds;
    }

    /**
     * @throws HelperException
     */
    public function deleteFormStatus(int $statusId): bool
    {
        $success = CFormStatus::Delete($statusId, 'N');
        if (!$success) {
            throw new HelperException($GLOBALS['strError']);
        }
        return true;
    }

    /**
     * @throws HelperException
     */
    public function deleteFormField(int $fieldId): bool
    {
        $success = CFormField::Delete($fieldId, 'N');
        if (!$success) {
            throw new HelperException($GLOBALS['strError']);
        }
        return true;
    }

    public function getFormStatuses(int $formId): array
    {
        $by = 's_sort';
        $order = 'asc';

        $dbres = CFormStatus::GetList($formId, $by, $order);
        return $this->fetchAll($dbres);
    }

    public function exportFormStatuses(int $formId): array
    {
        return $this->unsetKeysFromCollection([
            'ID',
            'TIMESTAMP_X',
            'FORM_ID',
            'RESULTS',
        ], $this->getFormStatuses($formId));
    }

    public function getFormFields(int $formId, array $fieldSids = []): array
    {
        $dbres = CFormField::GetList($formId, 'ALL');
        $fields = $this->fetchAll($dbres);

        if (!empty($fieldSids)) {
            $fields = array_filter($fields, function ($item) use ($fieldSids) {
                return in_array($item['SID'], $fieldSids);
            });
            return array_values($fields);
        }

        return $fields;
    }

    public function exportFormFields(int $formId, array $fieldSids = []): array
    {
        $fields = $this->getFormFields($formId, $fieldSids);
        foreach ($fields as $index => $field) {
            $fieldId = $field['ID'];

            $field = $this->unsetKeys([
                'ID',
                'TIMESTAMP_X',
                'FORM_ID',
                'VARNAME',
            ], $field);

            $field['ANSWERS'] = $this->unsetKeysFromCollection([
                'ID',
                'FIELD_ID',
                'QUESTION_ID',
                'TIMESTAMP_X',
            ], $this->getFieldAnswers($fieldId));

            $field['VALIDATORS'] = $this->unsetKeysFromCollection([
                'ID',
                'FORM_ID',
                'FIELD_ID',
                'TIMESTAMP_X',
                'PARAMS_FULL',
            ], $this->getFieldValidators($fieldId));

            $fields[$index] = $field;
        }

        return $fields;
    }

    /**
     * @throws HelperException
     */
    public function deleteFormIfExists(string $formSid): bool
    {
        $formId = $this->getFormId($formSid);

        if (!$formId) {
            return false;
        }
        if (CForm::Delete($formId)) {
            return true;
        }

        throw new HelperException(
            Locale::getMessage('ERR_CANT_DELETE_FORM', ['#NAME#' => $formSid])
        );
    }

    protected function getFieldAnswers(int $fieldId): array
    {
        $by = 's_sort';
        $order = 'asc';

        $dbres = CFormAnswer::GetList($fieldId, $by, $order);
        return $this->fetchAll($dbres);
    }

    protected function getFieldValidators(int $fieldId): array
    {
        $by = 's_sort';
        $order = 'asc';

        $dbres = CFormValidator::GetList($fieldId, [], $by, $order);
        return $this->fetchAll($dbres);
    }

    /**
     * @throws HelperException
     */
    protected function exportRights(int $formId): array
    {
        $userGroupHelper = new UserGroupHelper();
        $rights = [];

        try {
            $dbres = FormGroupTable::getList(['filter' => ['FORM_ID' => $formId]]);

            while ($group = $dbres->fetch()) {
                $groupCode = $userGroupHelper->getGroupCode($group['GROUP_ID']);
                if ($groupCode) {
                    $rights[$groupCode] = $group['PERMISSION'];
                }
            }
        } catch (Exception $e) {
            throw new HelperException($e->getMessage(), $e->getCode(), $e);
        }

        return $rights;
    }

    protected function exportSites(int $formId): array
    {
        return ($formId > 0) ? CForm::GetSiteArray($formId) : [];
    }

    protected function exportMailTemplates(int $formId): array
    {
        $templateIds = ($formId > 0) ? CForm::GetMailTemplateArray($formId) : [];

        $templates = [];
        foreach ($templateIds as $templateId) {
            $templates[] = (new EventHelper())->getEventMessageUidFilterById($templateId);
        }

        return $templates;
    }

    protected function exportMenus(int $formId): array
    {
        $dbres = CForm::GetMenuList(['FORM_ID' => $formId], 'N');
        return $this->fetchAll($dbres, 'LID', 'MENU');
    }

    /**
     * @throws HelperException
     */
    protected function saveFieldAnswers($fieldId, array $answers): array
    {
        $currentAnswers = $this->getFieldAnswers($fieldId);

        $updatedIds = [];

        foreach ($answers as $answer) {
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

            $answerId = CFormAnswer::Set($answer, $answerId);

            if (empty($answerId)) {
                throw new HelperException($GLOBALS['strError']);
            }
        }

        foreach ($currentAnswers as $currentAnswer) {
            if (!in_array($currentAnswer['ID'], $updatedIds)) {
                CFormAnswer::Delete($currentAnswer['ID'], $fieldId);
            }
        }

        return $updatedIds;
    }

    /**
     * @throws HelperException
     */
    protected function saveFieldValidators(int $formId, int $fieldId, array $validators): array
    {
        CFormValidator::Clear($fieldId);

        $validatorIds = [];

        foreach ($validators as $validator) {
            $validatorId = CFormValidator::Set(
                $formId,
                $fieldId,
                $validator['NAME'],
                $validator['PARAMS'],
                $validator['C_SORT']
            );

            if (empty($validatorId)) {
                throw new HelperException($GLOBALS['strError']);
            }

            $validatorIds[] = $validatorId;
        }

        return $validatorIds;
    }

    /**
     * @throws HelperException
     */
    private function replaceField(int $formId, int $fieldId, array $field): int
    {
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

        $fieldId = CFormField::Set($field, $fieldId, 'N');

        if (empty($fieldId)) {
            throw new HelperException($GLOBALS['strError']);
        }

        $this->saveFieldAnswers($fieldId, $answers);
        $this->saveFieldValidators($formId, $fieldId, $validators);

        return $fieldId;
    }
}
