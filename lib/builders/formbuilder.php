<?php

namespace Sprint\Migration\Builders;

use Bitrix\Main\Db\SqlQueryException;
use Sprint\Migration\Exceptions\ExchangeException;
use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class FormBuilder extends VersionBuilder
{
    protected function isBuilderEnabled()
    {
        return $this->getHelperManager()->Form()->isEnabled();
    }

    protected function initialize()
    {
        $this->setTitle(Locale::getMessage('BUILDER_FormExport1'));
        $this->setGroup('Form');

        $this->addVersionFields();
    }

    /**
     * @throws ExchangeException
     * @throws RebuildException
     * @throws SqlQueryException
     * @throws HelperException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $forms = $helper->Form()->getList();

        $structure = [];
        foreach ($forms as $item) {
            $structure[] = [
                'title' => '[' . $item['ID'] . '] ' . $item['NAME'],
                'value' => $item['ID'],
            ];
        }

        $formId = $this->addFieldAndReturn('form_id', [
            'title' => Locale::getMessage('BUILDER_FormExport_FormId'),
            'width' => 250,
            'select' => $structure,
        ]);

        $form = $helper->Form()->getFormById($formId);
        $this->exitIfEmpty($form, 'Form not found');

        unset($form['ID']);
        unset($form['TIMESTAMP_X']);
        unset($form['VARNAME']);

        $what = $this->addFieldAndReturn('what_else', [
            'title' => Locale::getMessage('BUILDER_FormExport_What'),
            'width' => 250,
            'multiple' => 1,
            'value' => [],
            'select' => [
                [
                    'title' => Locale::getMessage('BUILDER_FormExport_Form'),
                    'value' => 'form',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_FormExport_Fields'),
                    'value' => 'fields',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_FormExport_Statuses'),
                    'value' => 'statuses',
                ],
            ],
        ]);

        $formExport = false;
        if (in_array('form', $what)) {
            $formExport = true;
        }

        $statuses = [];
        if (in_array('statuses', $what)) {
            $statuses = $helper->Form()->getFormStatuses($formId);
            foreach ($statuses as $index => $status) {
                unset($status['ID']);
                unset($status['TIMESTAMP_X']);
                unset($status['FORM_ID']);
                unset($status['RESULTS']);
                $statuses[$index] = $status;
            }
        }

        $fields = [];
        if (in_array('fields', $what)) {
            $fields = $helper->Form()->getFormFields($formId);
            foreach ($fields as $index => $field) {
                unset($field['ID']);
                unset($field['TIMESTAMP_X']);
                unset($field['FORM_ID']);
                unset($field['VARNAME']);

                if (is_array($field['ANSWERS'])) {
                    foreach ($field['ANSWERS'] as $answerIndex => $answer) {
                        unset($answer['ID']);
                        unset($answer['FIELD_ID']);
                        unset($answer['QUESTION_ID']);
                        unset($answer['TIMESTAMP_X']);

                        $field['ANSWERS'][$answerIndex] = $answer;
                    }
                }


                if (is_array($field['VALIDATORS'])) {
                    foreach ($field['VALIDATORS'] as $validatorIndex => $validator) {
                        unset($validator['ID']);
                        unset($validator['FORM_ID']);
                        unset($validator['FIELD_ID']);
                        unset($validator['TIMESTAMP_X']);
                        unset($validator['PARAMS_FULL']);

                        $field['VALIDATORS'][$validatorIndex] = $validator;
                    }
                }

                $fields[$index] = $field;
            }
        }

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/FormExport.php',
            [
                'formExport' => $formExport,
                'form' => $form,
                'statuses' => $statuses,
                'fields' => $fields,
            ]
        );
    }
}
