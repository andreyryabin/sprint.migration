<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
use Sprint\Migration\Exceptions\RebuildException;
use Sprint\Migration\Fields\CheckboxListField;
use Sprint\Migration\Fields\RadioListField;
use Sprint\Migration\Locale;
use Sprint\Migration\Module;
use Sprint\Migration\VersionBuilder;

class FormBuilder extends VersionBuilder
{
    protected function initialize(): void
    {
        $this->setEnabled($this->getHelperManager()->Form()->isEnabled());

        $this->setTitle(Locale::getMessage('BUILDER_FormExport1'));
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Form'));

        $this->addVersionFields();
    }

    /**
     * @throws MigrationException
     * @throws RebuildException
     * @throws HelperException
     */
    protected function execute(): void
    {
        $helper = $this->getHelperManager();

        $formId = $this->askField('form_id',
            (new RadioListField(Locale::getMessage('BUILDER_FormExport_FormId')))
                ->setOptions($this->getFormsSelect(), 'ID', 'NAME'),
        );

        $form = $helper->Form()->exportFormById($formId);

        $what = $this->askField('what',
            (new CheckboxListField(Locale::getMessage('BUILDER_FormExport_What')))
                ->addOption('form', Locale::getMessage('BUILDER_FormExport_Form'))
                ->addOption('statuses', Locale::getMessage('BUILDER_FormExport_Statuses'))
                ->addOption('fields', Locale::getMessage('BUILDER_FormExport_Fields')),
        );

        $fields = [];
        $fieldsMode = '';

        if (in_array('fields', $what)) {
            $fieldsMode = $this->askField('fields_mode',
                (new RadioListField(Locale::getMessage('BUILDER_FormExport_Fields')))
                    ->setInPlanForValues('all')
                    ->addOption('all', Locale::getMessage('BUILDER_SelectAll'))
                    ->addOption('some', Locale::getMessage('BUILDER_SelectSome')),
            );

            if ($fieldsMode == 'some') {
                $fieldsSomeSids = $this->askField('fields_some',
                    (new CheckboxListField(Locale::getMessage('BUILDER_FormExport_Fields')))
                        ->setOptions($this->getFieldsSelect($formId), 'SID', 'TITLE'),
                );
                $fields = $helper->Form()->exportFormFields($formId, $fieldsSomeSids);
            }
            if ($fieldsMode == 'all') {
                $fields = $helper->Form()->exportFormFields($formId);
            }
        }

        $formExport = false;
        if (in_array('form', $what)) {
            $formExport = true;
        }

        $statuses = [];
        if (in_array('statuses', $what)) {
            $statuses = $helper->Form()->exportFormStatuses($formId);
        }

        $this->confirmCreateVersion();

        $this->createVersionFile(
            Module::getModuleTemplateFile('FormExport'),
            [
                'formExport' => $formExport,
                'fieldsMode' => $fieldsMode,
                'form'       => $form,
                'statuses'   => $statuses,
                'fields'     => $fields,
            ],
        );
    }

    private function getFormsSelect(): array
    {
        $helper = $this->getHelperManager();
        $items = $helper->Form()->getList();

        return array_map(function ($item) {
            $item['NAME'] = '[' . $item['SID'] . '] ' . $item['NAME'];
            return $item;
        }, $items);
    }

    private function getFieldsSelect(int $formId): array
    {
        $helper = $this->getHelperManager();
        $items = $helper->Form()->getFormFields($formId);

        return array_map(function ($item) {
            $item['TITLE'] = '[' . $item['SID'] . '] ' . $item['TITLE'];
            return $item;
        }, $items);
    }
}
