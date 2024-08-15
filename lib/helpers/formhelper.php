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
        $form = $this->export(
            $this->getFormById($formId),
            $this->getDefaultForm(),
            $this->getUnsetKeysForm()
        );

        $form['arSITE'] = $this->exportSites($formId);

        $form["arMENU"] = $this->exportMenus($formId);

        $form['arGROUP'] = $this->exportRights($formId);

        $form['arMAIL_TEMPLATE'] = $this->exportMailTemplates($formId);

        return $form;
    }

    /**
     * @throws HelperException
     */
    public function getFormById(int $formId): array
    {
        $form = CForm::GetByID($formId)->Fetch();
        if (empty($form)) {
            throw new HelperException("Form \"$formId\" not found");
        }
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
     * @noinspection PhpUnused
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

        $form = $this->merge($form, $this->getDefaultForm());

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
     * @noinspection PhpUnused
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
        $oldStatuses = $this->getFormStatuses($formId);

        $updatedIds = [];
        foreach ($statuses as $status) {
            $updatedIds[] = $this->saveStatus($formId, $status);
        }

        foreach ($oldStatuses as $status) {
            if (!in_array($status['ID'], $updatedIds)) {
                $this->deleteFormStatus($status['ID']);
            }
        }

        return $updatedIds;
    }

    /**
     * @throws HelperException
     */
    public function saveStatus(int $formId, array $fields): int
    {
        $this->checkRequiredKeys($fields, ['TITLE']);

        $statusId = $this->getStatusId($formId, $fields['TITLE']);
        if ($statusId) {
            return $this->updateStatus($formId, $statusId, $fields);
        }

        return $this->addStatus($formId, $fields);
    }

    public function getStatusId(int $formId, string $title): int
    {
        $by = 's_sort';
        $order = 'asc';

        //жаль что фильтр TITLE_EXACT_MATCH не работает

        $dbres = CFormStatus::GetList($formId, $by, $order);
        $statuses = $this->fetchAll($dbres);

        foreach ($statuses as $status) {
            if ($status['TITLE'] == $title) {
                return $status['ID'];
            }
        }
        return 0;
    }

    /**
     * @throws HelperException
     */
    public function updateStatus(int $formId, int $statusId, array $fields): int
    {
        $fields = $this->merge($fields, $this->getDefaultStatus());

        $fields['FORM_ID'] = $formId;

        $statusId = CFormStatus::Set($fields, $statusId, 'N');
        if (empty($statusId)) {
            throw new HelperException($GLOBALS['strError']);
        }

        return $statusId;
    }

    /**
     * @throws HelperException
     */
    public function addStatus(int $formId, array $fields): int
    {
        $fields = $this->merge($fields, $this->getDefaultStatus());

        $fields['FORM_ID'] = $formId;

        $statusId = CFormStatus::Set($fields, false, 'N');
        if (empty($statusId)) {
            throw new HelperException($GLOBALS['strError']);
        }

        return $statusId;
    }

    /**
     * @throws HelperException
     */
    public function addNewStatuses(int $formId, array $statuses): array
    {
        $updatedIds = [];
        foreach ($statuses as $status) {
            $updatedIds[] = $this->addStatus($formId, $status);
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

        $statuses = [];
        $dbres = CFormStatus::GetList($formId, $by, $order);
        while ($item = $dbres->Fetch()) {
            $statuses[] = $this->prepareStatus($item);
        }
        return $statuses;
    }

    protected function prepareStatus(array $item): array
    {
        //см. \bitrix\modules\form\admin\form_edit.php#295

        CFormStatus::GetPermissionList(
            $item['ID'],
            $arPERMISSION_VIEW,
            $arPERMISSION_MOVE,
            $arPERMISSION_EDIT,
            $arPERMISSION_DELETE
        );

        $item['arPERMISSION_VIEW'] = $arPERMISSION_VIEW;
        $item['arPERMISSION_MOVE'] = $arPERMISSION_MOVE;
        $item['arPERMISSION_EDIT'] = $arPERMISSION_EDIT;
        $item['arPERMISSION_DELETE'] = $arPERMISSION_DELETE;
        return $item;
    }

    public function exportFormStatuses(int $formId): array
    {
        return $this->exportCollection(
            $this->getFormStatuses($formId),
            $this->getDefaultStatus(),
            $this->getUnsetKeysStatus()
        );
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
        return array_map(function ($field) {
            $field['ANSWERS'] = $this->exportCollection(
                $this->getFieldAnswers($field['ID']),
                $this->getDefaultAnswer(),
                $this->getUnsetKeysAnswer()
            );
            $field['VALIDATORS'] = $this->exportCollection(
                $this->getFieldValidators($field['ID']),
                $this->getDefaultValidator(),
                $this->getUnsetKeysValidator(),
            );

            return $this->export(
                $field,
                $this->getDefaultField(),
                $this->getUnsetKeysField(),
            );
        }, $this->getFormFields($formId, $fieldSids));
    }

    /**
     * @noinspection PhpUnused
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
        $field = $this->merge($field, $this->getDefaultField());

        $field['FORM_ID'] = $formId;
        $field['VARNAME'] = $field['SID'];

        $answers = [];
        if (isset($field['ANSWERS'])) {
            if (is_array($field['ANSWERS'])) {
                $answers = $this->mergeCollection(
                    $field['ANSWERS'],
                    $this->getDefaultAnswer()
                );
            }
            unset($field['ANSWERS']);
        }

        $validators = [];
        if (isset($field['VALIDATORS'])) {
            if (is_array($field['VALIDATORS'])) {
                $validators = $this->mergeCollection(
                    $field['VALIDATORS'],
                    $this->getDefaultValidator()
                );
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

    private function getUnsetKeysForm(): array
    {
        return [
            'ID',
            'TIMESTAMP_X',
            'VARNAME',
            'C_FIELDS',
            'QUESTIONS',
            'STATUSES',
        ];
    }

    private function getDefaultForm(): array
    {
        return [
            'BUTTON'                 => 'Сохранить',
            'C_SORT'                 => '100',
            'FIRST_SITE_ID'          => null,
            'IMAGE_ID'               => null,
            'USE_CAPTCHA'            => 'N',
            'DESCRIPTION'            => '',
            'DESCRIPTION_TYPE'       => 'text',
            'FORM_TEMPLATE'          => '',
            'USE_DEFAULT_TEMPLATE'   => 'Y',
            'SHOW_TEMPLATE'          => null,
            'SHOW_RESULT_TEMPLATE'   => null,
            'PRINT_RESULT_TEMPLATE'  => null,
            'EDIT_RESULT_TEMPLATE'   => null,
            'FILTER_RESULT_TEMPLATE' => null,
            'TABLE_RESULT_TEMPLATE'  => null,
            'USE_RESTRICTIONS'       => 'N',
            'RESTRICT_USER'          => '0',
            'RESTRICT_TIME'          => '0',
            'RESTRICT_STATUS'        => '',
            'STAT_EVENT1'            => 'form',
            'STAT_EVENT2'            => '',
            'STAT_EVENT3'            => '',
            'LID'                    => null,
        ];
    }

    private function getUnsetKeysField(): array
    {
        return [
            'ID',
            'TIMESTAMP_X',
            'FORM_ID',
            'VARNAME',
        ];
    }

    private function getDefaultField(): array
    {
        return [
            'ACTIVE'           => 'Y',
            'C_SORT'           => '100',
            'ADDITIONAL'       => 'N',
            'REQUIRED'         => 'N',
            'IN_FILTER'        => 'Y',
            'IN_RESULTS_TABLE' => 'Y',
            'IN_EXCEL_TABLE'   => 'Y',
            'FIELD_TYPE'       => 'text',
            'IMAGE_ID'         => null,
            'COMMENTS'         => '',
        ];
    }

    private function getDefaultStatus(): array
    {
        return [
            'C_SORT'              => '100',
            'ACTIVE'              => 'Y',
            'DESCRIPTION'         => 'DEFAULT',
            'DEFAULT_VALUE'       => 'Y',
            'HANDLER_OUT'         => null,
            'HANDLER_IN'          => null,
            'arPERMISSION_VIEW'   => [0],
            'arPERMISSION_MOVE'   => [0],
            'arPERMISSION_EDIT'   => [0],
            'arPERMISSION_DELETE' => [0],
        ];
    }

    private function getUnsetKeysStatus(): array
    {
        return [
            'ID',
            'TIMESTAMP_X',
            'FORM_ID',
            'RESULTS',
        ];
    }

    private function getUnsetKeysAnswer(): array
    {
        return [
            'ID',
            'FIELD_ID',
            'QUESTION_ID',
            'TIMESTAMP_X',
        ];
    }

    private function getDefaultAnswer(): array
    {
        return [
            'MESSAGE'      => ' ',
            'VALUE'        => '',
            'FIELD_WIDTH'  => '0',
            'FIELD_HEIGHT' => '0',
            'FIELD_PARAM'  => '',
            'C_SORT'       => '0',
            'ACTIVE'       => 'Y',
        ];
    }

    private function getUnsetKeysValidator(): array
    {
        return [
            'ID',
            'FORM_ID',
            'FIELD_ID',
            'TIMESTAMP_X',
            'PARAMS_FULL',
        ];
    }

    private function getDefaultValidator(): array
    {
        return [
            'ACTIVE' => 'Y',
            'C_SORT' => '100',
        ];
    }
}
