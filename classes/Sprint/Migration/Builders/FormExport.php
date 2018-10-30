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
        $helper = new HelperManager();
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

//        $this->addField('what_else', array(
//            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_What'),
//            'width' => 250,
//            'multiple' => 1,
//            'value' => array(),
//            'select' => [
//                [
//                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_Form'),
//                    'value' => 'body'
//                ],
//                [
//                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_Rights'),
//                    'value' => 'rights'
//                ],
//                [
//                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_Templates'),
//                    'value' => 'templates'
//                ],
//            ]
//        ));

//        $what = $this->getFieldValue('what_else');
//        if (!empty($what)) {
//            $what = is_array($what) ? $what : array($what);
//        } else {
//            $this->rebuildField('what_else');
//        }

        $form = $formHelper->getFormById($formId);
        $this->exitIfEmpty($form, 'Form not found');


        unset($form['ID']);
        unset($form['TIMESTAMP_X']);
        unset($form['VARNAME']);

        $statuses = $formHelper->getFormStatuses($formId);

        foreach ($statuses as $index => $status){
            unset($status['ID']);
            unset($status['TIMESTAMP_X']);
            unset($status['FORM_ID']);
            unset($status['RESULTS']);
            $statuses[$index] = $status;
        }

        $fields = $formHelper->getFormFields($formId);

        $validators = $formHelper->getFormValidators($formId);

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/FormExport.php', array(
            'form' => $form,
            'statuses' => $statuses,
            'fields' => $fields,
            'validators' => $validators,
        ));
    }
}