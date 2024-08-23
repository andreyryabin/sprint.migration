<?php

namespace Sprint\Migration\Builders;

use Sprint\Migration\Exceptions\HelperException;
use Sprint\Migration\Exceptions\MigrationException;
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
        $this->setGroup(Locale::getMessage('BUILDER_GROUP_Form'));

        $this->addVersionFields();
    }

    /**
     * @throws MigrationException
     * @throws RebuildException
     * @throws HelperException
     */
    protected function execute()
    {
        $helper = $this->getHelperManager();

        $formId = $this->addFieldAndReturn('form_id', [
            'title'  => Locale::getMessage('BUILDER_FormExport_FormId'),
            'width'  => 250,
            'select' => $this->getFormsSelect(),
        ]);

        $form = $helper->Form()->exportFormById($formId);

        $what = $this->addFieldAndReturn('what', [
            'title'    => Locale::getMessage('BUILDER_FormExport_What'),
            'width'    => 250,
            'multiple' => 1,
            'value'    => [],
            'select'   => [
                [
                    'title' => Locale::getMessage('BUILDER_FormExport_Form'),
                    'value' => 'form',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_FormExport_Statuses'),
                    'value' => 'statuses',
                ],
                [
                    'title' => Locale::getMessage('BUILDER_FormExport_Fields'),
                    'value' => 'fields',
                ],
            ],
        ]);

        $fields = [];
        $fieldsMode = '';

        if (in_array('fields', $what)) {
            $fieldsMode = $this->addFieldAndReturn(
                'fields_mode',
                [
                    'title'  => Locale::getMessage('BUILDER_FormExport_Fields'),
                    'width'  => 250,
                    'select' => [
                        [
                            'title' => Locale::getMessage('BUILDER_SelectAll'),
                            'value' => 'all',
                        ],
                        [
                            'title' => Locale::getMessage('BUILDER_SelectSome'),
                            'value' => 'some',
                        ],
                    ],
                ]
            );

            if ($fieldsMode == 'some') {
                $fieldsSomeSids = $this->addFieldAndReturn(
                    'fields_some',
                    [
                        'title'    => Locale::getMessage('BUILDER_FormExport_Fields'),
                        'width'    => 250,
                        'multiple' => 1,
                        'value'    => [],
                        'select'   => $this->getFieldsSelect($formId),
                    ]
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

        $this->createVersionFile(
            Module::getModuleDir() . '/templates/FormExport.php',
            [
                'formExport' => $formExport,
                'fieldsMode' => $fieldsMode,
                'form'       => $form,
                'statuses'   => $statuses,
                'fields'     => $fields,
            ]
        );
    }

    private function getFormsSelect(): array
    {
        $helper = $this->getHelperManager();
        $items = $helper->Form()->getList();

        $items = array_map(function ($item) {
            $item['NAME'] = '[' . $item['SID'] . '] ' . $item['NAME'];
            return $item;
        }, $items);

        return $this->createSelect($items, 'ID', 'NAME');
    }

    private function getFieldsSelect(int $formId): array
    {
        $helper = $this->getHelperManager();
        $items = $helper->Form()->getFormFields($formId);

        $items = array_map(function ($item) {
            $item['TITLE'] = '[' . $item['SID'] . '] ' . $item['TITLE'];
            return $item;
        }, $items);

        return $this->createSelect($items, 'SID', 'TITLE');
    }
}
