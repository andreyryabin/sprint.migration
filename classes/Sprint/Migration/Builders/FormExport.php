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

        $this->addField('form_id', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_FormId'),
            'width' => 250,
        ));

        $this->addField('description', array(
            'title' => GetMessage('SPRINT_MIGRATION_FORM_DESCR'),
            'width' => 350,
            'height' => 40,
        ));
    }

    protected function execute()
    {
        $helper = new HelperManager();
        $formHelper = $helper->Form();

        $formId = $this->getFieldValue('form_id');
        $this->exitIfEmpty($formId, 'Form id is not valid');

        $form = $formHelper->initForm($formId);
        $this->exitIfEmpty($form, 'Form not found');

        $statuses = $formHelper->getFormStatuses();
        $form['_STATUSES'] = $statuses;

        $fields = $formHelper->getFormFields();
        $form['_FIELDS'] = $fields;

        $validators = $formHelper->getFormValidators();
        $form['_VALIDATORS'] = $validators;

        debmes($form);
    }
}