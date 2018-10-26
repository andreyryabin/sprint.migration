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
        $this->addField('sid', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport3'),
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

        $this->addField('what_else', array(
            'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_What'),
            'width' => 250,
            'multiple' => 1,
            'value' => array(),
            'select' => [
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_Form'),
                    'value' => 'body'
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_Rights'),
                    'value' => 'rights'
                ],
                [
                    'title' => GetMessage('SPRINT_MIGRATION_BUILDER_FormExport_Templates'),
                    'value' => 'templates'
                ],
            ]
        ));

        $what = $this->getFieldValue('what_else');
        if (!empty($what)) {
            $what = is_array($what) ? $what : array($what);
        } else {
            $this->rebuildField('what_else');
        }

        $formId = $this->getFieldValue('form_id');
        $this->exitIfEmpty($formId, 'Form id is not valid');

        $form = [];
        $formBody = $formHelper->getFormById($formId);
        $this->exitIfEmpty($formBody, 'Form not found');

        /**
         * Внимание! Последние 2 поля ориентируются на ID сущностей, так что могут быть расхождения. Применяйте на свой страх и риск
         * В будущем переработать на модель при которой шаблоны будут задаваться явно со всеми данными, а группы пользователей находиться по символьному коду в целевой системе
         * Сейчас же эти поля надо проверять вручную или же не использовать вообще
         */
        if(in_array('rights', $what)){
            $rights = $formHelper->getRights($formId);
            $formBody['arGROUP'] = $rights;
        }
        if(in_array('templates', $what)){
            $templates = $formHelper->getMailTemplates($formId);
            $formBody['arMAIL_TEMPLATE'] = $templates;
        }
        $form['FORM'] = $formBody;

        $statuses = $formHelper->getFormStatuses($formId);
        $form['STATUSES'] = $statuses;

        $fields = $formHelper->getFormFields($formId);
        $form['FIELDS'] = $fields;

        $validators = $formHelper->getFormValidators($formId);
        $form['VALIDATORS'] = $validators;

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/FormExport.php', array(
            'form' => $form,
            'description' => $this->getFieldValue('description'),
            'sid' => $this->getFieldValue('sid')
        ));
    }
}