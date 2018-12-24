<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;
use Sprint\Migration\HelperManager;
use Sprint\Migration\Exceptions\HelperException;

class FormExport extends VersionBuilder
{
    protected function isBuilderEnabled() {
        return (\CModule::IncludeModule('form'));
    }

    protected function initialize() {
        $this->setTitle(GetMessage('SPRINT_MIGRATION_BUILDER_FormExport1'));
        $this->setDescription(GetMessage('SPRINT_MIGRATION_BUILDER_FormExport2'));

        $this->addField('prefix', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_PREFIX'),
            'value' => $this->getVersionConfig()->getVal('version_prefix'),
            'width' => 250,
        ));

        $this->addField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ));
    }

    protected function execute() {
        $helper = HelperManager::getInstance();
        $formHelper = $helper->Form();

        $forms = $formHelper->getList();

        $structure = array();
        foreach ($forms as $item) {
            $structure[] = array(
                'title' => '[' . $item['ID'] . '] ' . $item['NAME'],
                'value' => $item['ID'],
            );
        }

        $this->addField('form_id', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_FormId'),
            'width' => 250,
            'select' => $structure
        ));

        $formId = $this->getFieldValue('form_id');
        if (empty($formId)) {
            $this->rebuildField('form_id');
        }

        $form = $formHelper->getFormById($formId);
        $this->exitIfEmpty($form, 'Form not found');

        unset($form['ID']);
        unset($form['TIMESTAMP_X']);
        unset($form['VARNAME']);

        $this->addField('what_else', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_What'),
            'width' => 250,
            'multiple' => 1,
            'value' => array(),
            'select' => [
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_Form'),
                    'value' => 'form'
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_Fields'),
                    'value' => 'fields'
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_Statuses'),
                    'value' => 'statuses'
                ],
            ]
        ));

        $what = $this->getFieldValue('what_else');
        if (!empty($what)) {
            $what = is_array($what) ? $what : array($what);
        } else {
            $this->rebuildField('what_else');
        }


        $formExport = false;
        if (in_array('form', $what)) {
            $formExport = true;
        }


        $statuses = array();
        if (in_array('statuses', $what)) {
            $statuses = $formHelper->getFormStatuses($formId);
            foreach ($statuses as $index => $status) {
                unset($status['ID']);
                unset($status['TIMESTAMP_X']);
                unset($status['FORM_ID']);
                unset($status['RESULTS']);
                $statuses[$index] = $status;
            }
        }


        $fields = array();
        if (in_array('fields', $what)) {
            $fields = $formHelper->getFormFields($formId);
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
            Module::getModuleDir() . '/templates/FormExport.php', array(
            'formExport' => $formExport,
            'form' => $form,
            'statuses' => $statuses,
            'fields' => $fields,
        ));
    }
}